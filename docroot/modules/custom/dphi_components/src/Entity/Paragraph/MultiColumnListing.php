<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\ListsFieldTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;


#[Bundle(
  entityType: 'paragraph',
  bundle: 'multi_column_listing',
)]
class MultiColumnListing extends Paragraph {

  use ListsFieldTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'number_of_column' => $this->getSingleFieldValue('field_number_of_columns_per_row'),
      'lists' => $this->getMultiColumnLists(),
    ];
  }
}
