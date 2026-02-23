<?php

namespace Drupal\dphi_components\Drush\Commands;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drush\Commands\DrushCommands;
use Drupal\Core\Batch\BatchBuilder;

class QuickLinksMigrationCommand extends DrushCommands {

  /**
   * Replace 'Quick Links' with 'Multi-column listing' in the Middle Left and Bottom
   *
   * @command dphi_components:quick-links-migration
   * @param string $operation
   */
  public function processPages(string $operation) {
    if (!in_array($operation, ['create', 'delete', 'fix'])) {
      echo 'Unknown operation';
      return;
    }

    // Initialize the batch builder.
    $batch_builder = new BatchBuilder();
    $batch_builder->setTitle(t('Processing all page nodes'))
      ->setInitMessage(t('Batch processing starting...'))
      ->setProgressMessage(t('Processed @current out of @total.'))
      ->setErrorMessage(t('Batch processing encountered an error.'));

    // Get all node IDs of type 'page'.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'page')
      ->accessCheck(FALSE)
      ->execute();

    // Define the operation for each batch.
    $batch_builder->addOperation([$this, 'processNode'], [$operation, $nids]);

    // Set the batch and start the processing.
    batch_set($batch_builder->toArray());
    drush_backend_batch_process();
  }

  protected static function recreateItems($items) {
    $result = [];
    foreach ($items as $item) {
      $link = $item->get('field_link')->getValue();
      if (!$link) {
        continue;
      }
      $type = $item->get('field_links_type')->getValue();
      $paragraph = Paragraph::create([
        'type'=>'list_item',
        'field_link'=>$link[0]['uri'],
        'field_links_type'=>$type ? $type[0]['value'] : 2,
        'field_link_text'=>$item->get('field_link_text')->getValue()[0]['value']
      ]);
      $paragraph->save();
      $result[] = $paragraph;
    }
    return $result;
  }

  /**
   * Batch operation callback to process each node.
   *
   * @param string $operation
   * @param array $nids
   *   An array of node IDs to process.
   * @param array $context
   *   The batch context array.
   */
  public static function processNode(string $operation, array $nids, array &$context) {
    // If this is the first time running, set the total number of operations.
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['nids'] = $nids;
      $context['sandbox']['total'] = count($nids);
      $context['sandbox']['current'] = 0;
    }

    // Get the current node ID to process.
    $nid = array_shift($context['sandbox']['nids']);
    if ($nid) {
      $node = Node::load($nid);

      $anyUpdate = false;
      $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
      foreach ($nodeStorage->revisionIds($node) as $revisionId) {
        $node = $nodeStorage->loadRevision($revisionId);
        if (!$node) {
          continue;
        }
        foreach (['field_middle_left_section', 'field_bottom_section'] as $section) {
          $sectionEntity = $node->get($section)->entity;
          if (!$sectionEntity || !$sectionEntity->hasField('field_section_content')) {
            continue;
          }
          $paragraphs = $sectionEntity->get('field_section_content')->referencedEntities();
          $newParagraphs = [];
          $updated = false;
          foreach ($paragraphs as $paragraph) {
            if ($paragraph->getType() == 'quick_links') {
              $links = $paragraph->get('field_links')->referencedEntities();
              if (!$links) {
                $newParagraphs[] = $paragraph;
                continue;
              }
              $anyUpdate = true;
              $updated = true;
              if ($operation != 'delete') {
                $newParagraphs[] = $paragraph;
              }
              if ($operation == 'create') {
                $lists = Paragraph::create([
                  'type'=>'lists',
                  'field_list_items'=>self::recreateItems($links)
                ]);
                $lists->save();
                $multi_column_listing_paragraph = Paragraph::create([
                  'type' => 'multi_column_listing',
                  'field_title' => $paragraph->get('field_title'),
                  'field_padding_control' => 'standard',
                  'field_number_of_columns_per_row' => '12',
                  'field_lists' => $lists
                ]);
                $multi_column_listing_paragraph->save();
                $newParagraphs[] = $multi_column_listing_paragraph;
              }
            } else {
              $newParagraphs[] = $paragraph;
              if ($operation == 'fix' && $paragraph->getType() == 'multi_column_listing') {
                $lists = $paragraph->get('field_lists')->referencedEntities();
                if ($lists) {
                  $list = $lists[0];
                  $items = $list->get('field_list_items')->referencedEntities();
                  if (!empty($items[0])) {
                    if ($items[0]->getType() == 'links') {
                      $anyUpdate = true;
                      $list->set('field_list_items', self::recreateItems($items));
                      $list->save();
                    }
                  }
                }
              }
            }
          }
          if ($updated) {
            $sectionEntity->set('field_section_content', $newParagraphs);
            $sectionEntity->save();
          }
        }
      }
      if ($anyUpdate) {
        $context['message'] = t('Processed node ID @nid', ['@nid' => $nid]);
      } else {
        $context['message'] = t('No change required for node ID @nid', ['@nid' => $nid]);
      }

      // Update the sandbox data.
      $context['sandbox']['current']++;
      $context['finished'] = $context['sandbox']['current'] / $context['sandbox']['total'];

    }
  }

}
