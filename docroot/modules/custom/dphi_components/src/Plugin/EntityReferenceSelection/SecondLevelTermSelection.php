<?php

namespace Drupal\dphi_components\Plugin\EntityReferenceSelection;

use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides specific access control for the taxonomy_term entity type, allowing selection of second level terms.
 *
 * @EntityReferenceSelection(
 *   id = "second_level_term_selection",
 *   label = @Translation("Second Level Term Selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "second_level_term_selection",
 *   weight = 0
 * )
 */
class SecondLevelTermSelection extends TermSelection
{

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS')
  {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Limit to second-level terms by ensuring the parent is not 0.
    // This assumes second-level terms have parents that are first-level terms.
    $query->condition('parent', 0, '!=');

    return $query;
  }

}
