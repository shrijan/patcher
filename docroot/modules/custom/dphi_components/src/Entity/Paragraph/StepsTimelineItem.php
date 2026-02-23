<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'steps_timeline_item',
)]
class StepsTimelineItem extends Paragraph {

  use FieldValueTrait;

  public function getComponent(): array {
    return [
      'heading' => $this->getSingleFieldValue('field_heading'),
      'content' => $this->getContentFieldValue('field_content'),
      'complete' => $this->getSingleFieldValue('field_mark_as_complete')
    ];
  }

}
