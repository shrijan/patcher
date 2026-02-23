<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\Core\Url;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'quick_links',
)]
class QuickLinks extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'icon' => $this->getIcon('field_icon'),
      'icon_outlined' => $this->isIconOutlined('field_icon'),
      'icon_class' => $this->getIconClass('field_icon'),
      'list_items' => $this->getListItems()
    ];
  }

  public function getListItems(): array {
    $list_items = [];
    $list_items_paragraph = $this->get('field_links')->referencedEntities();
    foreach ($list_items_paragraph as $item_paragraph) {
      $list_item['link_type'] = $item_paragraph->getSingleFieldValue('field_links_type');
      $list_item['link_text'] = $item_paragraph->getSingleFieldValue('field_link_text');
      $link = $item_paragraph->getSingleFieldValue('field_link');
      if ($link) {
        $list_item['link'] = Url::fromUri($link)->toString();
      }
      $list_items[] = $list_item;
    }
    return $list_items;
  }
}
