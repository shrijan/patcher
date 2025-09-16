<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'steps_timeline',
)]
class StepsTimeline extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'background_color' => $this->getSingleFieldValue('field_background_colours'),
      'color' => $this->getSingleFieldValue('field_colour_variants'),
      'heading_size' => $this->getSingleFieldValue('field_heading_size'),
      'number' => $this->getSingleFieldValue('field_show_steps_count'),
      'items' => array_map(function ($item) {
        return $item->getComponent();
      }, $this->get('field_steps_timeline_item')->referencedEntities())
    ];
  }

}
