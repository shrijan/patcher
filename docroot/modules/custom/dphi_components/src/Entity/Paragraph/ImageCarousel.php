<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'image_carousel',
)]
class ImageCarousel extends Paragraph {

  use FieldValueTrait;

  public function getComponent(): array {
    $image = $this->get('field_im_image');
    $video = $this->get('field_im_video');
    return [
      'text' => $this->getSingleFieldValue('field_im_description_caption'),
      'text_background' => $this->getSingleFieldValue('field_text_background'), // transparent/grey/light/dark
      'image' => $image->entity ? $image->view('media') : null,
      'video' => $video->entity ? $video->view('media') : null,
      'camera_icon' => $this->getBooleanValue('field_show_camera_icon'),
    ];
  }

}
