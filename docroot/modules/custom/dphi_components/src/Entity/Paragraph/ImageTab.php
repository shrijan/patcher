<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'image_tab_component',
)]
class ImageTab extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent() {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'use_background_color' => $this->getSingleFieldValue('field_use_background_colour') == '1',
      'items' => array_map(function ($tab) {
        return $tab->getComponent();
      }, $this->get('field_tabs')->referencedEntities())
    ];
  }

}
