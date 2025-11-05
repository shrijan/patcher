<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'title_block',
)]
class TitleBlock extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $node = $this->getParentEntity();
    while ($node instanceof Paragraph) {
      $node = $node->getParentEntity();
    }
    if ($this->getSingleFieldValue('field_hide_date') == '1' || !$node->hasField('field_publish_event_date')) {
      $date = '';
    } else {
      $date = $node->get('field_publish_event_date')->view([
        'type' => 'datetime_custom',
        'settings' => ['date_format' => 'd F Y']
      ]);
    }
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'titleAsH1' => $this->getSingleFieldValue('field_display_title_as_h1') == '1',
      'date' => $date,
      'tags' => $this->getSingleFieldValue('field_hide_tag') == '1' || !$node->hasField('field_tags') ? [] : array_map(function ($tag) {
        return $tag->label();
      }, $node->get('field_tags')->referencedEntities())
    ];
  }

}
