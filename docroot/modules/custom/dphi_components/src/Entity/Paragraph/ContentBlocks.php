<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'content_blocks',
)]
class ContentBlocks extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent() {
    return [
      'callout_background' => $this->getSingleFieldValue('field_callout_background'),
      'layout' => $this->getSingleFieldValue('field_layout'),
      'varients' => array_map(function ($varient) {
        return array_merge($varient->getComponent(), [
          'type' => $this->getSingleFieldValue('field_content_block_type'),
          'background' => $this->getSingleFieldValue('field_callout_background')
        ]);
      }, $this->get('field_content_block_varients')->referencedEntities())
    ];
  }

}
