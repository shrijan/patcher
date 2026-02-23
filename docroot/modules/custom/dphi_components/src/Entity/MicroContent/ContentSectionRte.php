<?php

namespace Drupal\dphi_components\Entity\MicroContent;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\microcontent\Entity\MicroContent;

#[Bundle(
  entityType: 'microcontent',
  bundle: 'content_section_rte',
)]
class ContentSectionRte extends MicroContent {

  use FieldValueTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'content' => $this->getContentFieldValue('field_content'),
      'date' => $this->getSingleFieldValue('field_date'),
      'display_as_h2' => $this->getSingleFieldValue('field_display_title_as_h2'),
      'use_background_colour' => $this->getSingleFieldValue('field_use_light_gray_background'),
    ];
  }
}
