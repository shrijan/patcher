<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'content_block_varient',
)]
class ContentBlockVariant extends Paragraph {

  use FieldValueTrait;

  public function getComponent(): array {
    $media = $this->get('field_media_image');
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'content' => $this->getContentFieldValue('field_description'),
      'image' => $media->entity ? $media->view('media') : null,
      'camera_icon' => $this->getBooleanValue('field_show_camera_icon'),
      'links' => $this->get('field_link_item'),
      'viewMore' => $this->get('field_view_more')->first()
    ];
  }

}
