<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\ListsFieldTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'lists',
)]
class Lists extends Paragraph {

  use ListsFieldTrait;

  public function getComponent(): array {
    return [
      'lists' => $this->getLists($this),
    ];
  }

}
