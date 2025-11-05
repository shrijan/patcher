<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'curated_content_list',
)]
class CuratedCardList extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'description' => $this->getSingleFieldValue('field_subheading'),
      'use_background_color' => $this->getSingleFieldValue('field_hc_use_background_color'),
      'cards' => $this->getCards(),
    ];
  }

  public function getCards(): array {
    return array_map(function($card) {
      $title = $card->get('field_hc_title')->first();
      $content = $card->get('field_hc_description');
      $data = [
        'theme' => $this->getSingleFieldValue('field_hc_theme'),
        'highlight' => $this->getBooleanValue('field_hc_highlight'),
        'configuration' => 'horizontal',
        'title' => $title ? $title->view() : '',
        'description' => !$content->isEmpty() ? $content->view(['label' => 'hidden']) : null,
        'image' => $card->get('field_media_image')->view('media'),
        'link' => $card->get('field_link')->first()->getUrl(),
        'endIcon' => 'east',
      ];
      return $data;
    }, $this->get('field_cards')->referencedEntities());
  }

}
