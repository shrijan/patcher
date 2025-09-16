<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\Core\Url;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'links',
)]
class Links extends Paragraph {

  use FieldValueTrait;

  public function getComponent(): array {
    $link = Url::fromUri($this->getSingleFieldValue('field_link'));
    $url = $link->toString();
    return [
      'text' => $this->getSingleFieldValue('field_link_text'),
      'type' => $this->getSingleFieldValue('field_links_type'),
      'url' => $url,
    ];
  }
}
