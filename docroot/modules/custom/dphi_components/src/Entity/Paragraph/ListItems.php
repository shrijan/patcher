<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Entity\ListItemsInterface;
use Drupal\dphi_components\Traits\ListItemsFieldTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'list_items',
)]
class ListItems extends Paragraph implements ListItemsInterface {

  use ListItemsFieldTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'description' => $this->getContentFieldValue('field_description'),
      'items' => $this->getListItems(),
    ];
  }

  public function getListItems(): array {
    return array_map(function($listItem) {
      return $listItem->getComponent();
    }, $this->get('field_list_items')->referencedEntities());
  }


}
