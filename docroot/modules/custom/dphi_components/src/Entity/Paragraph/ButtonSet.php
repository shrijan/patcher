<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'button_sets',
)]
class ButtonSet extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent() {

    $buttons_paragraph = $this->get('field_button')->getValue();
    $buttons = [];
    if (!empty($buttons_paragraph)) {
      foreach ($buttons_paragraph as $element) {
        $item = [];
        $p = Paragraph::load($element['target_id']);
        if (!$p->get('field_link_text')->isEmpty()) {
          $item['text'] = $p->get('field_link_text')->view([
            'label' => 'hidden',
            'type' => 'string',
          ]);
        }
        if (!$p->get('field_link')->isEmpty()) {
          $item['url'] = $p->get('field_link')->first()->getUrl();
        }
        $buttons[] = $item;
      }
    }

    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'description' => $this->getContentFieldValue('field_description'),
      'use_background_color' => $this->getBooleanValue('field_use_background_colour'),
      'buttons' => $buttons
    ];
  }

}
