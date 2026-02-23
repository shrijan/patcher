<?php

namespace Drupal\dphi_components\Plugin\EntityReferenceSelection;

use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "first_level_term_selection",
 *   label = @Translation("First Level Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "first_level_term_selection",
 *   weight = 0
 * )
 */
class FirstLevelTermSelection extends TermSelection
{

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS')
  {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Limit to first-level terms by ensuring the parent is 0 (or absent).
    $query->condition('parent', 0);
    return $query;
  }

}
