<?php

namespace Drupal\dphi_components\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\ConditionGroup;

class NodeQueryService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NodeQueryService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Fetches node IDs based on selected types and tags.
   *
   * @param array $selected_types
   *   An array of selected content types.
   * @param array $selected_tags
   *   An array of selected tags.
   *
   * @return array
   *   An array of node IDs.
   */
  public function fetchNodeIds(array $selected_types, array $selected_tags, $sort_order, $limit = NULL) {
    $database = \Drupal::database();
    $query = $database->select('node', 'base_table');
    $query->addField('base_table', 'nid');
    $query->innerJoin('node_field_data', 'node_field_data', 'node_field_data.nid = base_table.nid');
    $query->condition('node_field_data.status', 1);

    $category_types = $types = [];
    if ($selected_types) {
      foreach ($selected_types as $type) {
        // For 'publications', add condition based on content type.
        if ($type === 'publications') {
          $types[] = 'publications';
        }
        else {
          $types[] = 'page';
          $category_types[] = $type;
        }
      }
    }
    else {
      $types = ['publications', 'page'];
    }

    // Add condition for content types.
    $conditions = $query->andConditionGroup()->condition('base_table.type', $types, 'IN');

    // Add conditions for category types.
    if ($category_types) {
      $query->leftJoin('node__field_content_category', 'node__field_content_category', 'node__field_content_category.entity_id = base_table.nid');
      $conditions->condition($query->orConditionGroup()->condition('node__field_content_category.field_content_category_value', $category_types, 'IN'));

      // Add conditions for selected tags.
      if (!empty($selected_tags)) {
        $query->leftJoin('node__field_tags', 'node__field_tags', 'node__field_tags.entity_id = base_table.nid');
        $conditions->condition($query->orConditionGroup()->condition('node__field_tags.field_tags_target_id', $selected_tags, 'IN'));
      }
    }

    // Add OR condition for field area.
    if (in_array('publications', $types) && !empty($selected_tags)) {
      $query->leftJoin('node__field_area', 'node__field_area', 'node__field_area.entity_id = base_table.nid');
      if($category_types) {
        $or_condition = $query->orConditionGroup()
          ->condition('node__field_area.field_area_target_id', $selected_tags, 'IN');
        $query->condition($query->orConditionGroup()
          ->condition($conditions)
          ->condition($or_condition));
      }
      else {
        $conditions->condition($query->orConditionGroup()->condition('node__field_area.field_area_target_id', $selected_tags, 'IN'));
        $query->condition($conditions);
      }
    } else {
      $query->condition($conditions);
    }


      // Default Order by.
      switch ($sort_order) {
          case 'title_desc':
              $query->orderBy('node_field_data.title', 'DESC');
              break;
          case 'date_desc':
              $query->leftJoin('node__field_publish_event_date', 'node__field_publish_event_date', 'node__field_publish_event_date.entity_id = base_table.nid');
              $query->orderBy('node__field_publish_event_date.field_publish_event_date_value', 'DESC');
              break;
          case 'date_asc':
              $query->addExpression('field_publish_event_date_value IS NULL', 'field_publish_event_date_is_null');
              $query->leftJoin('node__field_publish_event_date', 'node__field_publish_event_date', 'node__field_publish_event_date.entity_id = base_table.nid');

              $query->orderBy('field_publish_event_date_is_null', 'ASC');
              $query->orderBy('node__field_publish_event_date.field_publish_event_date_value', 'ASC');
              break;
          default:
              $query->orderBy('node_field_data.title', 'ASC');
              break;
      }



      // Apply the limit if provided.
    if (is_numeric($limit)) {
      $query->range(0, $limit);
    }

    try {
      return $query->execute()->fetchCol();
    }
    catch (\Exception $e) {
      \Drupal::logger('content_listing')
        ->error('Error fetching nodes: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

}
