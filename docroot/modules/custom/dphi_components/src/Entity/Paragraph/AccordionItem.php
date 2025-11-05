<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'accordion_item',
)]
class AccordionItem extends Paragraph {

  use FieldValueTrait;

  public function getComponent() {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'content' => $this->getContentFieldValue('field_content')
    ];
  }

}
