<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'global_components',
)]
class GlobalComponents extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent() {
    $paddingControlValue = $this->getPaddingControlValue();
    $paddingControlClass = $this->getPaddingControlClass();
    $items = $this->get('field_select_content')->view('default');

    foreach ($items as $key => &$item) {
      if (!is_numeric($key)) {
        continue;
      }
      $item['#padding_control'] = $paddingControlClass;
      $item['#cache']['keys'][] = $paddingControlValue;
    }

    return $items;
  }

}
