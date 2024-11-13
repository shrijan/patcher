<?php

namespace Drupal\Tests\scanner\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\user\Entity\User;

/**
 * Helper test class with some added functions for testing.
 */
abstract class ScannerTestBase extends BrowserTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'paragraphs', 'views', 'scanner'];

  /**
   * Log in as user 1.
   */
  protected function loginUser1() {
    // Load user 1.
    /** @var \Drupal\user\Entity\User $account */
    $account = User::load(1);

    // Reset the password.
    $password = 'foo';
    $account->setPassword($password)->save();

    // Support old and new tests.
    $account->passRaw = $password;
    $account->pass_raw = $password;

    // Login.
    $this->drupalLogin($account);
  }

  /**
   * {@inheritdoc}
   */
  protected function verbose($message, $title = NULL) {
    // Handle arrays, objects, etc.
    if (!is_string($message)) {
      $message = "<pre>\n" . print_r($message, TRUE) . "\n</pre>\n";
    }

    // Optional title to go before the output.
    if (!empty($title)) {
      $title = '<h2>' . Html::escape($title) . "</h2>\n";
    }

    parent::verbose($title . $message);
  }

  /**
   * Create a vocabulary.
   *
   * @param array $values
   *   Items passed to the vocabulary. If the 'vid' item is not present it will
   *   be automatically generated. If the 'name' item is not present the 'vid'
   *   will be used.
   *
   * @return \Drupal\taxonomy\VocabularyInterface|\Drupal\Core\Entity\EntityInterface
   *   A fully formatted vocabulary object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createVocabulary(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['vid'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (Vocabulary::load($id));
    }
    else {
      $id = $values['vid'];
    }
    $values += [
      'vid' => $id,
      'name' => $id,
    ];
    $vocab = Vocabulary::create($values);
    $status = $vocab->save();

    $this->assertEquals($status, SAVED_NEW, (new FormattableMarkup('Created vocabulary %type.', ['%type' => $vocab->id()]))->__toString());

    return $vocab;
  }

  /**
   * Create a taxonomy term.
   *
   * @param array $values
   *   Items passed to the term. Requires the 'vid' element.
   *
   * @return \Drupal\taxonomy\TermInterface|\Drupal\Core\Entity\EntityInterface
   *   A fully formatted term object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTerm(array $values = []) {
    // Populate defaults array.
    $values += [
      'description' => [
        [
          'value' => $this->randomMachineName(32),
          'format' => filter_default_format(),
        ],
      ],
      'name' => $this->randomMachineName(8),
    ];
    $term = Term::create($values);
    $status = $term->save();

    $this->assertEquals($status, SAVED_NEW, (new FormattableMarkup('Created term %name.', ['%name' => $term->label()]))->__toString());

    return $term;
  }

  /**
   * Create a content type and a node.
   *
   * @param string $title
   *   A title for the node that will be returned.
   * @param string $body
   *   The text to use as the body.
   * @param string $content_type
   *   The node bundle type.
   * @param string $content_type_label
   *   The content type label.
   *
   * @return \Drupal\node\NodeInterface
   *   A fully formatted node object.
   */
  protected function createContentTypeNode($title, $body, $content_type, $content_type_label) {
    $args = [
      'type' => $content_type,
      'name' => $content_type_label,
    ];
    $this->createContentType($args);

    $args = [
      'body' => [
        [
          'value' => $body,
          'format' => filter_default_format(),
        ],
      ],
      'title' => $title,
      'type' => $content_type,
    ];

    return $this->createNode($args);
  }

  /**
   * Prints out the debug information about the test database connection.
   */
  protected function debugDatabaseConnection() {
    $connection = self::getDatabaseConnection();
    $this->addWarning(var_export([
      'database type' => $connection->databaseType(),
      'database client version' => $connection->clientVersion(),
      'database version' => $connection->version(),
      'database is mariadb' => (method_exists($connection, 'isMariaDb') && $connection->isMariaDb()),
      'database driver' => $connection->driver(),
    ], TRUE));
  }

}
