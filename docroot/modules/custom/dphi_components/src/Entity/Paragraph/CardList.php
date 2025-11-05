<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'card_list',
)]
class CardList extends Paragraph {

  use PaddingControlTrait;
  use FieldValueTrait;

  public function getComponent(): array {
    $configuration = $this->getSingleFieldValue('field_card_configuration');
    if ($configuration == 'fullwidth') {
      $items_per_row = 1;
    } else if ($configuration == 'horizontal') {
      $items_per_row = 2;
    } else {
      // Default to 2 when no choice selected.
      $items_per_row = $this->getBooleanValue('field_items_per_row') ?? 2;
    }
    return [
      'background_theme' => $this->getSingleFieldValue('field_cl_background_theme'),
      'title' => $this->getSingleFieldValue('field_title'),
      'description' => $this->getContentFieldValue('field_description'),
      'description_on_left' => $this->getBooleanValue('field_keep_description_on_left'),
      'items_per_row' => $items_per_row,
      'cards' => array_map(function ($card) use ($configuration) {
        $card['configuration'] = $configuration;
        return $card;
      }, $this->getCards()),
    ];
  }

  public function getCards(): array {
    return array_map(function($card) {
      $content = $card->get('field_content');
      $title = $card->get('field_title')->first();
      $data = [
        'theme' => $this->getSingleFieldValue('field_theme'),
        'highlight' => $this->getSingleFieldValue('field_highlight') == '1',
        'title' => $title ? $title->view() : '',
        'description' => !$content->isEmpty() ? $content->view(['label' => 'hidden']) : null,
      ];
      if ($card->getType() == 'card') {
        $icon = $card->get('field_icon')->first();
        $media = $card->get('field_media_image');
        $link = $card->get('field_link')->first()->getUrl();
        $number = $card->get('field_number_text')->first();
        $camera_icon = $card->get('field_show_camera_icon')->value;
        $data = array_merge($data, [
          'icon' => $icon ? trim($icon->get('icon')->getValue()) : NULL,
          'iconClasses' => $icon ? $icon->get('classes')->getValue() : NULL,
          'iconOutlined' => $icon ? $icon->get('family')->getValue() == 'symbols__outlined' : false,
          'image' => $media->entity ? $media->view('media') : NULL,
          'camera_icon' => $camera_icon,
          'link' => $link,
          'number' => $number ? $number->get('value')->getValue() : NULL,
          'endIcon' => $link ? 'east' : NULL,
          'tags' => array_map(function($tag) {
            return $tag->label();
          }, $card->get('field_card_tags')->referencedEntities()),
        ]);
      }
      else {
        $file = $card->get('field_file_download');
        $file_uri = $file->entity->getFileUri();
        $data = array_merge($data, [
          'link' => \Drupal::service('file_url_generator')
            ->generate($file_uri)
            ->toString(),
          'image' => $file->view($file->entity->getMimeType() == 'application/pdf' ? 'pdfpreview' : 'media'),
          'image_fullHeight' => TRUE,
          'endIcon' => 'download',
        ]);
      }
      return $data;
    }, $this->get('field_cards')->referencedEntities());
  }

}
