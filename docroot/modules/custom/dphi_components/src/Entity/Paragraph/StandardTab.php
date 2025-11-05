<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'standard_tab',
)]
class StandardTab extends Paragraph {

  use FieldValueTrait;

  public function getComponent() {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'content' => $this->get('field_tab_content')->view(),
    ];
  }

}
