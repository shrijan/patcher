<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'hero',
)]
class Hero extends Paragraph {

  use FieldValueTrait;

  public function getComponent(): array {
    $theme = $this->getSingleFieldValue('field_set_title_background');
    $hero_type = $this->getSingleFieldValue('field_hero_type');
    $component = [
      'type' => $hero_type,
      'camera_icon' => $this->getBooleanValue('field_show_camera_icon'),
      'title' => $this->getSingleFieldValue('field_title'),
      'intro' => $this->getSingleFieldValue('field_subheading')
    ];
    $link = $this->get('field_link');
    if (!$link->isEmpty()) {
      $component['link'] = [
        ...$this->formatLinkData($link->first()),
      ];
    }
    if ($image = $this->getHeroImage('field_media_background_image')) {
      $component = array_merge($component, $image);
    }
    if (in_array($hero_type, ['0', '1', '3', '5'])) {
      $component['theme'] = $theme;
    }
    if (in_array($hero_type, ['0', '2', '4'])) { // Image on right, card or homepage banner
      $component['image'] = TRUE;
    }
    elseif($hero_type == '6') {
      $component['description'] = $this->getContentFieldValue('field_description');
      $component['text_alignment'] = $this->getSingleFieldValue('field_text_alignment');
    }
    if (in_array($hero_type, ['2', '4', '6'])) { // Card or homepage banner
      $component['card_color'] = $this->getSingleFieldValue('field_text_hero_background_color');
    }
    if ($hero_type == '2') { // Card
      $component['center_align'] = $this->getSingleFieldValue('field_left_align_title_and_subhe') != '1';
    } else if ($hero_type == '3') { // Featured list
      $component['sub_header'] = $this->getSingleFieldValue('field_links_sub_header');
      $component['links'] = [];
      foreach ($this->get('field_hero_links') as $link) {
        $component['links'][] = array_merge($this->formatLinkData($link), [
          'classes' => $theme == 'dark' ? 'nsw-text--light' : ''
        ]);
      }
    }
    return $component;
  }

  private function formatLinkData($link): array {
    return [
      'text' => $link->title,
      'url' => $link->getUrl()
    ];
  }
}
