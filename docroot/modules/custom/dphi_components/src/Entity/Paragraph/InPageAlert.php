<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'in_page_alert',
)]
class InPageAlert extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'content' => $this->getContentFieldValue('field_content'),
      'compact' => $this->getSingleFieldValue('field_alert_compact'),
      'type' => $this->getSingleFieldValue('field_type'),
    ];
  }

}
