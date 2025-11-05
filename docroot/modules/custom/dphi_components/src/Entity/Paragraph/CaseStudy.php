<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'case_study_component',
)]
class CaseStudy extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent() {
    return [
      'column_alignment' => $this->getSingleFieldValue('field_1_column_alignment'),
      'column_layout' => $this->getSingleFieldValue('field_column_layout'),
      'colour' => $this->getSingleFieldValue('field_colour_variants'),
      'show_border' => $this->getSingleFieldValue('field_show_border') == '1',
      'items' => array_map(function ($item) {
        $title = $item->get('field_title')->first();
        $content = $item->get('field_content')->view(['label' => 'hidden']);
        $icon = $item->get('field_icon')->first();
        $media = $item->get('field_image');
        return [
          'heading' => $title ? $title->view() : null,
          'icon' => $icon ? $icon->get('icon')->getValue() : null,
          'iconOutlined' => $icon ? $icon->get('family')->getValue() == 'symbols__outlined' : false,
          'image' => $media->entity ? $media->view('media') : null,
          'content'=>$content,
        ];
      }, $this->get('field_case_study_items')->referencedEntities())
    ];
  }

}
