<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\scanner\Plugin\ScannerPluginBase;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * A generic Scanner plugin for handling entities.
 *
 * @Scanner(
 *   id = "scanner_entity",
 *   type = "entity",
 * )
 */
class Entity extends ScannerPluginBase {

  /**
   * The scanner regular expression.
   *
   * @var string
   */
  protected $scannerRegexChars = '.\/+*?[^]$() {}=!<>|:';

  /**
   * Performs the serach operation for the given string/expression.
   *
   * @param string $field
   *   The field with the matching string (formatted as type:bundle:field).
   * @param array $values
   *   An array containing the $form_state values.
   *
   * @return array
   *   An array containing the titles of the entity and a snippet of the
   *   matching text.
   */
  public function search($field, array $values) {
    $data = [];
    [$entityType] = explode(':', $field);

    // Attempt to load the matching plugin for the matching entity.
    try {
      $plugin = $this->scannerManager->createInstance("scanner_$entityType");
      if (empty($plugin)) {
        throw new PluginException('Unable to load entity type ' . $entityType . '.');
      }
    }
    catch (PluginException $e) {
      // The instance could not be found so fail gracefully and let the user
      // know.
      \Drupal::logger('scanner')->error($e->getMessage());
      \Drupal::messenger()->addError($this->t('An error occured @e:', ['@e' => $e->getMessage()]));
    }

    // Perform the search on the current field.
    $results = $plugin->search($field, $values);
    if (!empty($results)) {
      $data = $results;
    }

    return $data;
  }

  /**
   * Performs the replace operation for the given string/expression.
   *
   * @param string $field
   *   The field with the matching string (formatted as type:bundle:field).
   * @param array $values
   *   An array containing the $form_state values.
   * @param array $undo_data
   *   An array containing the data.
   *
   * @return array
   *   An array containing the revisoion ids of the affected entities.
   */
  public function replace($field, array $values, array $undo_data) {
    $data = [];
    [$entityType] = explode(':', $field);

    try {
      $plugin = $this->scannerManager->createInstance("scanner_$entityType");
    }
    catch (PluginException $e) {
      // The instance could not be found so fail gracefully and let the user
      // know.
      \Drupal::logger('scanner')->error($e->getMessage());
      \Drupal::messenger()->addError('An error occured: ' . $e->getMessage());
    }

    // Perform the replace on the current field and save results.
    $results = $plugin->replace($field, $values, $undo_data);
    if (!empty($results)) {
      $data = $results;
    }

    return $data;
  }

  /**
   * Undo the replace operation by reverting entities to a previous revision.
   *
   * @param array $data
   *   An array containing the revision ids needed to undo the previous replace
   *   operation.
   */
  public function undo(array $data) {
    foreach ($data as $key => $value) {
      [$entityType] = explode(':', $key);
      // Attempt to load the matching plugin for the matching entity.
      try {
        $plugin = $this->scannerManager->createInstance("scanner_$entityType");
        $plugin->undo($value);
      }
      catch (PluginException $e) {
        \Drupal::logger('scanner')->error($e->getMessage());
        \Drupal::messenger()->addError('An error occured: ' . $e->getMessage());
      }
    }
  }

  /**
   * Helper function to "build" the proper query condition.
   *
   * @param string $search
   *   The string that is to be searched for.
   * @param bool $mode
   *   The boolean that indicated whether or not the search should be case
   *   sensitive.
   * @param bool $wholeword
   *   The boolean that indicates whether the search should be word bounded.
   * @param bool $regex
   *   The boolean that indicates whether or not the search term is a regular
   *   expression.
   * @param string $preceded
   *   The string for preceded expression.
   * @param string $followed
   *   The string for the succeeding expression.
   *
   * @return array
   *   Returns an associative array keyed by:
   *   - condition: The target ICU regular expression (for database use);
   *   - phpRegex: The target perl-compatible regular expression (for PHP);
   *   - operator: The operator to use in a database condition; usually one of
   *     'REGEXP', 'LIKE', 'REGEXP_LIKE'.
   */
  protected function buildCondition($search, $mode, $wholeword, $regex, $preceded, $followed) {
    $preceded_php = '';
    if (!empty($preceded)) {
      if (!$regex) {
        $preceded = addcslashes($preceded, $this->scannerRegexChars);
      }
      $preceded_php = '(?<=' . $preceded . ')';
    }
    $followed_php = '';
    if (!empty($followed)) {
      if (!$regex) {
        $followed = addcslashes($followed, $this->scannerRegexChars);
      }
      $followed_php = '(?=' . $followed . ')';
    }

    /** @var \Drupal\scanner\WordBoundariesHelper $word_boundaries_helper */
    $word_boundaries_helper = \Drupal::service('scanner.word_boundaries_helper');
    if ($word_boundaries_helper->whichToUse() === 'icu') {
      $word_boundary_prefix = $word_boundary_suffix = "\\b";
    }
    else {
      // Control which word boundaries are used, used for compatibility with
      // different releases of MySQL.
      // @see https://dev.mysql.com/doc/refman/8.0/en/regexp.html#regexp-compatibility
      // @see https://mariadb.com/kb/en/regular-expressions-overview/#word-boundaries
      $word_boundary_prefix = '[[:<:]]';
      $word_boundary_suffix = '[[:>:]]';
    }

    // Case 1.
    if ($wholeword && $regex) {
      $value = $word_boundary_prefix . $preceded . $search . $followed . $word_boundary_suffix;
      $operator = 'REGEXP';
      $phpRegex = '/\b' . $preceded_php . $search . $followed_php . '\b/';
    }
    // Case 2.
    elseif ($wholeword && !$regex) {
      $value = $word_boundary_prefix . $preceded . addcslashes($search, $this->scannerRegexChars) . $followed . $word_boundary_suffix;
      $operator = 'REGEXP';
      $phpRegex = '/\b' . $preceded_php . addcslashes($search, $this->scannerRegexChars) . $followed . '\b/';
    }
    // Case 3.
    elseif (!$wholeword && $regex) {
      $value = $preceded . $search . $followed;
      $operator = 'REGEXP';
      $phpRegex = '/' . $preceded_php . $search . $followed_php . '/';
    }
    // Case 4.
    else {
      $value = '%' . $preceded . addcslashes($search, $this->scannerRegexChars) . $followed . '%';
      $operator = 'LIKE';
      $phpRegex = '/' . $preceded . addcslashes($search, $this->scannerRegexChars) . $followed . '/';
    }

    if ($mode) {
      if ($operator === 'REGEXP' && $word_boundaries_helper->supportsInlinePcreFlags()) {
        // Check if the database engine supports the inline PCRE regex. If it
        // does, then we'll use that to specify the case-sensitivity rather than
        // the BINARY flag.
        // https://mariadb.com/kb/en/pcre/#option-setting
        $value = "(?-i)$value";
      }
      elseif ($operator === 'REGEXP' && $word_boundaries_helper->shouldUseRegexpLike()) {
        // Check if it supports the "REGEXP BINARY" syntax. If it does not, then
        // we'll use REGEXP_LIKE instead.
        $operator = 'REGEXP_LIKE';
      }
      else {
        $operator .= ' BINARY';
      }
    }
    else {
      $phpRegex .= 'i';
    }

    return [
      'condition' => $value,
      'phpRegex' => $phpRegex,
      'operator' => $operator,
    ];
  }

  /**
   * Ensures the query is limited by the specified search conditions.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to modify.
   * @param array $condition
   *   An array of condition values as returned by ::buildCondition().
   * @param string $fieldname
   *   The name of the entity field having a condition added.
   * @param bool $mode
   *   TRUE for a case-sensitive search; FALSE otherwise.
   * @param string $language
   *   The language code or 'all' for all languages.
   */
  protected function addQueryCondition(QueryInterface $query, array $condition, $fieldname, $mode, $language) {
    if ($language !== 'all') {
      $query->condition('langcode', $language, '=');
    }

    $langcode = $language === 'all' ? NULL : $language;
    if ($condition['operator'] === 'REGEXP_LIKE') {
      // The REGEXP_LIKE() function can't be added directly to the entity query,
      // so we'll add a tag and add the actual condition later.
      /* @see \scanner_query_scanner_search_regexp_like_alter() */
      /* @see \Drupal\scanner\Plugin\Scanner\Entity::addRegexpLikeCondition() */
      $query->addTag('scanner_search_regexp_like')
        ->addMetaData('scanner_search_regexp_like', [
          'entity_type_id' => $query->getEntityTypeId(),
          'fieldname' => $fieldname,
          'langcode' => $langcode,
          'mode' => $mode,
          'pattern' => $condition['condition'],
        ]);
    }
    else {
      $query->condition($fieldname, $condition['condition'], $condition['operator'], $langcode);
    }
  }

  /**
   * Adds a WHERE condition using the REGEXP_LIKE() function.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to modify.
   */
  public static function addRegexpLikeCondition(SelectInterface $query) {
    [
      'entity_type_id' => $entity_type_id,
      'fieldname' => $fieldname,
      'langcode' => $langcode,
      'mode' => $mode,
      'pattern' => $pattern,
    ] = $query->getMetaData('scanner_search_regexp_like');

    $entity_query = \Drupal::entityQuery($entity_type_id);
    // It will only work with an SQL entity query.
    if (!$entity_query instanceof Query) {
      return;
    }

    // Use the entity query helper to add the field to $query.
    $tables = $entity_query->getTables($query);
    $field = $tables->addField($fieldname, 'INNER', $langcode);

    $connection = \Drupal::database();
    // Escape the field name on Drupal 9+.
    // https://www.drupal.org/node/2986894
    $field = $connection->escapeField($field);

    // Add the conditional expression.
    $query->where("REGEXP_LIKE($field, :pattern, :match_type)", [
      ':match_type' => $mode ? 'c' : 'i',
      ':pattern' => $pattern,
    ]);
  }

}
