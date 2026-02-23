<?php

namespace Drupal\dphi_components\Entity\Node;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\node\Entity\Node;

#[Bundle(
  entityType: 'node',
  bundle: 'page',
)]
class Page extends Node {

  use FieldValueTrait;

  public function getSortDate(): string {
    $publication_date = $this->get('field_publish_event_date')->getString() ?? NULL;
    return $publication_date ? \Drupal::service('date.formatter')
      ->format(strtotime($publication_date), 'custom', 'Y-m-d') : '';
  }

}
