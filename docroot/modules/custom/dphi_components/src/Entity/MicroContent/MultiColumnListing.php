<?php

namespace Drupal\dphi_components\Entity\MicroContent;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\ListsFieldTrait;
use Drupal\microcontent\Entity\MicroContent;

#[Bundle(
  entityType: 'microcontent',
  bundle: 'multi_column_listing',
)]
class MultiColumnListing extends MicroContent {

  use ListsFieldTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'number_of_column' => $this->getSingleFieldValue('field_number_of_columns_per_row'),
      'lists' => $this->getMultiColumnLists(),
    ];
  }
}
