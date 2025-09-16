<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'content_block',
)]
class ContentBlock extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'use_background_color' => $this->getSingleFieldValue('field_use_background_colour') == '1',
      'title' => $this->getSingleFieldValue('field_title'),
      'titleAsH2' => $this->getSingleFieldValue('field_display_title_as_h2') == '1',
      'date' => $this->getSingleFieldValue('field_date'),
      'content' => $this->getContentFieldValue('field_content'),
    ];
  }

}
