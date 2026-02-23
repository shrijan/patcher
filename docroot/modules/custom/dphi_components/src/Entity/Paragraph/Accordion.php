<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'accordion',
)]
class Accordion extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent() {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'items' => array_map(function ($accordion) {
        return $accordion->getComponent();
      }, $this->get('field_accordions')->referencedEntities())
    ];
  }

}
