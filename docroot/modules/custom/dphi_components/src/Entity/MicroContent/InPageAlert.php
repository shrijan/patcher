<?php

namespace Drupal\dphi_components\Entity\MicroContent;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\microcontent\Entity\MicroContent;


#[Bundle(
  entityType: 'microcontent',
  bundle: 'in_page_alert',
)]
class InPageAlert extends MicroContent {

  use FieldValueTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_alert_title'),
      'content' => $this->getContentFieldValue('field_alert_content'),
      'compact' => $this->getSingleFieldValue('field_alert_compact'),
      'type' => $this->getSingleFieldValue('field_alert_type'),
    ];
  }
}
