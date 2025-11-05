<?php

namespace Drupal\dphi_components\Entity\Media;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\media\Entity\Media;

#[Bundle(
  entityType: 'media',
  bundle: 'image',
)]
class Image extends Media {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'image_credit' => $this->getSingleFieldValue('field_image_credit'),
      'image_description' => $this->getSingleFieldValue('field_image_description'),
    ];
  }

}
