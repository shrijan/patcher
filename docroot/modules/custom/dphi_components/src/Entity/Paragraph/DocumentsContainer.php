<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'documents_container',
)]
class DocumentsContainer extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_dc_title'),
      'titleAsH3' => $this->getBooleanValue('field_display_title_as_h3'),
      'show_date_column' => $this->getBooleanValue('field_display_date_column'),
      'documents' => array_map(function ($document) {
        return $document->getComponent();
      }, $this->get('field_documents')->referencedEntities())
    ];
  }
}
