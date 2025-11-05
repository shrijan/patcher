<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;


#[Bundle(
  entityType: 'paragraph',
  bundle: '50_50_component',
)]
class FiftyFifty extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $link = $this->get('field_link')->first();
    $component = [
      'type' => '0',
      'background_color' => $this->getSingleFieldValue('field_ti_background_color'),
      'text_60' => $this->getSingleFieldValue('field_description_height_short') == '1',
      'title' => $this->getSingleFieldValue('field_title'),
      'intro' => $this->getContentFieldValue('field_description'),
      'camera_icon' => $this->getBooleanValue('field_show_camera_icon'),
      'keep_image_on_left' => $this->getSingleFieldValue('field_keep_description_on_left') == '1',
      'link' => $link ? [
        'classes' => 'nsw-button nsw-button--dark',
        'text' => $link->title,
        'url' => $link->getUrl(),
      ] : null
    ];
    if ($image = $this->getHeroImage('field_image')) {
      $component = array_merge($component, $image);
      $component['image'] = TRUE;
    }
    return $component;
  }
}
