<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;


#[Bundle(
  entityType: 'paragraph',
  bundle: 'callout',
)]
class Callout extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'heading' => $this->getSingleFieldValue('field_heading'),
      'content' => $this->getContentFieldValue('field_content'),
      'background' => $this->getSingleFieldValue('field_callout_background'),
      'highlight_bar' => $this->getSingleFieldValue('field_callout_highlight_bar_colo'),
      'icon' => $this->getIcon('field_icon'),
      'icon_outlined' => $this->isIconOutlined('field_icon'),
    ];
  }
}
