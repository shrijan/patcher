<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;


#[Bundle(
  entityType: 'paragraph',
  bundle: 'statistic_item',
)]
class StatisticsItem extends Paragraph {

  use FieldValueTrait;

  public function getComponent(): array {
    $image = $this->get('field_image');
    return [
      'image' => $image->entity ? $image->view('media') : null,
      'title' => $this->getSingleFieldValue('field_title'),
      'content' => $this->getContentFieldValue('field_content'),
      'suffix' => $this->getSingleFieldValue('field_statistic_append_text')
    ];
  }
}
