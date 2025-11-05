<?php
namespace Drupal\dphi_components\Traits;

use Drupal\Core\Url;

trait ListsFieldTrait {
  use FieldValueTrait;

  public function getMultiColumnLists(): array {
    $items = [];
    $lists_paragraphs = $this->get('field_lists')->referencedEntities();
    foreach ($lists_paragraphs as $paragraph) {
      $items[] = $this->getLists($paragraph);
    }
    return $items;
  }

  public function getLists($paragraph): array {
    $item = [];
    if ($paragraph->get('field_title') && !$paragraph->get('field_title')->isEmpty()) {
      $item['title'] = $paragraph->get('field_title')->first()->get('value')->getValue();
    }
    if ($paragraph->hasField('field_icon') && !$paragraph->get('field_icon')->isEmpty()) {
      $icon = $paragraph->get('field_icon')->first();
      if ($icon && trim($icon->get('icon')->getValue())) {
        $item['icon'] = $paragraph->get('field_icon')->view();
      }
    }
    $list_item = [];
    $list_items_paragraph = $paragraph->get('field_list_items')->referencedEntities();
    foreach ($list_items_paragraph as $item_paragraph) {
      $list_item['link_type'] = $item_paragraph->getSingleFieldValue('field_links_type');
      $list_item['link_text'] = $item_paragraph->getSingleFieldValue('field_link_text');
      $field_link = $item_paragraph->getSingleFieldValue('field_link');
      $link = ($field_link != '') ? Url::fromUri($field_link) : '';
      $list_item['link'] = ($link == '') ? '' : $link->toString();
      $item['list_items'][] = $list_item;
    }
    return $item;
  }


}
