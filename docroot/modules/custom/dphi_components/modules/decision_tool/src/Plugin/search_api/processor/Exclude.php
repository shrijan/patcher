<?php

namespace Drupal\decision_tool\Plugin\search_api\processor;

use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Defines an Exclude search_api processor plugin.
 *
 * @package Drupal\search_api_exclude\Plugin\search_api\processor
 *
 * @SearchApiProcessor(
 *   id = "decision_tool_exclude",
 *   label = @Translation("Decision Tool exclude"),
 *   description = @Translation("Exclude Decision Tool nodes"),
 *   stages = {
 *     "alter_items" = 0
 *   }
 * )
 */
class Exclude extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() === 'node') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();

      $type = $object->getType();
      if ($type == 'question') {
        $exclude = true;
      } else if ($type == 'page') {
        $category = $object->get('field_content_category')[0];
        if ($category && $category->getValue()['value'] == 6) {
          $exclude = true;
        }
      }

      if (!empty($exclude)) {
        unset($items[$item_id]);
      }
    }
  }

}
