<?php

namespace Drupal\dphi_components\Drush\Commands;

use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;
use Drupal\Core\Batch\BatchBuilder;

class PaddingUpdateCommand extends DrushCommands {

  /**
   * Process all paragraphs using batch processing.
   *
   * @command dphi_components:padding-update
   */
  public function processPages() {
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
    $batch_builder->addOperation([$this, 'processNode'], [$nids]);

    // Set the batch and start the processing.
    batch_set($batch_builder->toArray());
    drush_backend_batch_process();
  }

  /**
   * Batch operation callback to process each paragraph.
   *
   * @param array $nids
   *   An array of node IDs to process.
   * @param array $context
   *   The batch context array.
   */
  public static function processNode(array $nids, array &$context) {
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

      $nodeUpdated = false;
      $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
      foreach ($nodeStorage->revisionIds($node) as $revisionId) {
        $node = $nodeStorage->loadRevision($revisionId);
        if (!$node) {
          continue;
        }
        foreach (['field_top_section', 'field_middle_left_section', 'field_middle_right_section', 'field_bottom_section'] as $section) {
          $sectionEntity = $node->get($section)->entity;
          if (!$sectionEntity || !$sectionEntity->hasField('field_section_content')) {
            continue;
          }
          $paragraphs = $sectionEntity->get('field_section_content')->referencedEntities();
          $newParagraphs = [];
          foreach ($paragraphs as $paragraph) {
            $padding_control = $paragraph->hasField('field_padding_control') ? $paragraph->get('field_padding_control')->first() : true;
            if (empty($padding_control)) {
              $nodeUpdated = true;
              $paragraph->set('field_padding_control', 'standard');
              $paragraph->save();
            }
          }
        }
      }
      if ($nodeUpdated) {
        $context['message'] = t('Processed node ID @nid', ['@nid' => $nid]);
      } else {
        $context['message'] = t('No change required for node ID @nid', ['@nid' => $nid]);
      }

      // Update the sandbox data.
      $context['sandbox']['current']++;
      $context['finished'] = $context['sandbox']['current'] / $context['sandbox']['total'];
      $context['message'] .= '. '.strval($context['sandbox']['current']).' out of '.strval($context['sandbox']['total']).' completed';
    }
  }

}
