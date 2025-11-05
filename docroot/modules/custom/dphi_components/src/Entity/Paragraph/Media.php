<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'media',
)]
class Media extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $link = $this->get('field_link')->first();
    $media = $this->get('field_media')->entity;
    return [
      'media' => $media ? \Drupal::entityTypeManager()->getViewBuilder('media')->view($media) : null,
      'link' => $link ? [
        'text' => $link->title,
        'url' => $link->getUrl()
      ] : null,
      'media_width' => $this->getSingleFieldValue('field_media_width') ?: 'nsw-media--100',
      'description_caption' => $this->getSingleFieldValue('field_description_caption'),
      'text_background' => $this->getSingleFieldValue('field_text_background'),
      'camera_icon' => $this->getBooleanValue('field_show_camera_icon'),
    ];
  }

}
