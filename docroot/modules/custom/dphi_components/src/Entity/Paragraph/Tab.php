<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'tab',
)]
class Tab extends Paragraph {

  use FieldValueTrait;

  public function getComponent() {
    $component = [
      'title' => $this->getSingleFieldValue('field_title'),
      'content' => $this->get('field_tab_content')->view(),
    ];
    foreach (['', '_active'] as $suffix) {
      $icon = $this->get('field_tab_head_icon'.$suffix);
      if ($icon->entity) {
        $icon_media = $icon->view('media');
        $icon_media[0]['#item']->alt = '';

        $component['icon'.$suffix] = $icon_media;
      }
    }
    return $component;
  }

}
