<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'map',
)]
class GalleryMap extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    // Load the relevant pins.
    $term = $this->get('field_pin_term')->referencedEntities()[0];

    // @var \Drupal\dphi_components\MapPinDataService $mapPinDataService
    $mapPinDataService = \Drupal::service('dphi_components.map_pin_data_service');
    return [
      'api_key' => $this->getSingleFieldValue('field_map_api_key'),
      'id' => $this->getSingleFieldValue('field_map_id'),
      'enable_clustering' => $this->getSingleFieldValue('field_enable_clustering'),
      // Add the pins JSON string as a variable to be used in the template.
      'pins_json_string' => json_encode($mapPinDataService->getFormattedMapPins($term)),
      // Fetch second-level terms for map filtering
      'terms_json_string' => json_encode($mapPinDataService->getSecondLevelTerms($term)),
      // Add the filter label as a variable to be used in the template.
      'filter_label' => $mapPinDataService->getFilterLabel($term),
      // Retrieve the Popup CTA Label for the first-level term.
      'modal_cta_label' => $mapPinDataService->getPopupCtaLabel($term),
      // Add First level term SVG icon
      'parent_svg_icon' => $mapPinDataService->getSvgIconUrlForTerm($term)
    ];
  }
}
