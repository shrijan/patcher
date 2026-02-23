<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'image_carousel_container',
)]
class ImageCarouselContainer extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'width' => $this->getSingleFieldValue('field_carousel_width'),
      'items' => array_map(function ($image) {
        return $image->getComponent();
      }, $this->get('field_image_carousel')->referencedEntities())
    ];
  }

}
