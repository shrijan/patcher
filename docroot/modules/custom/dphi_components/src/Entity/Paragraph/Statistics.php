<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;


#[Bundle(
  entityType: 'paragraph',
  bundle: 'statistics_component',
)]
class Statistics extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $color = $this->getSingleFieldValue('field_colour_variants') ?: 'brand-dark';
    $components = $this->get('field_statistic_items')->referencedEntities();
    $items = [];
    foreach ($components as $component) {
      $item = $component->getComponent();
      $item['color'] = $color;
      $items[] = $item;
    }
    return [
      'enable_dynamic_counter' => $this->getSingleFieldValue('field_enable_dynamic_counter') == '1',
      'show_border' => $this->getSingleFieldValue('field_show_border') == '1',
      'align' => $this->getSingleFieldValue('field_1_column_alignment'),
      'column_layout' => $this->getSingleFieldValue('field_column_layout'),
      'items' => $items,
    ];
  }
}
