<?php

namespace Drupal\dphi_components;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class MapPinDataService
{

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager)
  {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function getFormattedMapPins($firstLevelTerm)
  {
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Fetch child terms (second-level terms)
    $childTerms = $termStorage->loadChildren($firstLevelTerm->id());

    if (!$childTerms) {
      return []; // Return an empty array if there are no child terms
    }

    // Extract term IDs
    $childTermIds = array_keys($childTerms);

    // Fetch Map Pin entities that are enabled
    $mapPins = $this->entityTypeManager->getStorage('map_pin')
      ->loadByProperties(['field_pin_type' => $childTermIds, 'status' => 1]);

    // Format data for each Map Pin entity
    $formattedPins = [];
    foreach ($mapPins as $mapPin) {
      $formattedData = $mapPin->formatForFrontEnd();
      // Ensure that formatForFrontEnd does not return null or an empty array
      if (!empty($formattedData)) {
        $formattedPins[] = $formattedData;
      }
    }

    return $formattedPins;
  }


  public function getSecondLevelTerms($firstLevelTerm)
  {
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $query = $termStorage->getQuery()
      ->condition('parent', $firstLevelTerm->id())
      ->condition('status', 1)
      ->accessCheck(FALSE); // Explicitly set access check to FALSE.

    $termIds = $query->execute();

    if (empty($termIds)) {
      return []; // Return an empty array if there are no child terms
    }

    $childTerms = $termStorage->loadMultiple($termIds);

    // Sort terms by weight
    uasort($childTerms, function ($a, $b) {
      return $a->get('weight')->value <=> $b->get('weight')->value;
    });

    $formattedTerms = [];
    foreach ($childTerms as $term) {
      $svgIcon = $term->get('field_svg_icon')->getValue();
      $colour = $term->get('field_colour')->getValue();

      $svgIconUrl = null;
      if (!empty($svgIcon)) {
        $file = $this->entityTypeManager->getStorage('file')->load(reset($svgIcon)['target_id']);
        if ($file) {
          $svgIconUrl = $file->createFileUrl(FALSE);
        }
      }

      $colourCode = !empty($colour) ? reset($colour)['value'] : null;

      $formattedTerms[] = [
        'id' => $term->id(),
        'name' => $term->getName(),
        'iconUrl' => $svgIconUrl,
        'colour' => $colourCode,
      ];
    }

    return $formattedTerms;
  }


  // Extracts the first level term filter label for use in the filters select
  public function getFilterLabel($firstLevelTerm)
  {
    // Retrieve the field_filter_label value
    $filterLabel = $firstLevelTerm->get('field_filter_label')->value;

    return $filterLabel;
  }

  /**
   * Retrieves the Popup CTA Label for a first-level term.
   *
   * @param $firstLevelTermId
   *   The term ID of the first-level term.
   *
   * @return string|null
   *   The Popup CTA Label if found, or null otherwise.
   */
  public function getPopupCtaLabel($firstLevelTerm)
  {
    // Retrieve the field_popup_cta_label value
    $popupCtaLabel = $firstLevelTerm->get('field_popup_cta_label')->value;

    return $popupCtaLabel;
  }

  /**
   * Gets the SVG icon URL for a term.
   *
   * @param $termId
   *   The term ID.
   *
   * @return string|null
   *   The SVG icon URL or null if not found.
   */
  public function getSvgIconUrlForTerm($term)
  {
    $svgIcon = $term->get('field_svg_icon')->getValue();
    $svgIconUrl = null;
    if (!empty($svgIcon)) {
      $file = $this->entityTypeManager->getStorage('file')->load(reset($svgIcon)['target_id']);
      if ($file) {
        $svgIconUrl = $file->createFileUrl(FALSE);
      }
    }

    return $svgIconUrl;
  }
}
