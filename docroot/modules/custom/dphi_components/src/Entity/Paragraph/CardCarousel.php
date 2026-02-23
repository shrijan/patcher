<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'card_carousel',
)]
class CardCarousel extends Paragraph {

  use PaddingControlTrait;
  use FieldValueTrait;

  public function getComponent(): array {
    return [
      'background_theme' => $this->getSingleFieldValue('field_cl_background_theme'),
      'title' => $this->getSingleFieldValue('field_title'),
      'description' => $this->getContentFieldValue('field_description'),
      'description_on_left' => $this->getBooleanValue('field_keep_description_on_left'),
      'cards' => CardList::getCards($this)
    ];
  }
}
