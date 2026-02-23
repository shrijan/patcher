<?php

namespace Drupal\dphi_components\Entity\MicroContent;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\microcontent\Entity\MicroContent;

#[Bundle(
  entityType: 'microcontent',
  bundle: 'callout',
)]
class Callout extends MicroContent {
  use FieldValueTrait;

  public function getComponent(): array {
    return [
      'heading' => $this->getSingleFieldValue('field_heading'),
      'content' => $this->getContentFieldValue('field_content'),
      'background' => $this->getSingleFieldValue('field_callout_background'),
      'highlight_bar' => $this->getSingleFieldValue('field_callout_highlight_bar_colo'),
      'icon' => $this->getIcon('field_icon'),
    ];
  }
}
