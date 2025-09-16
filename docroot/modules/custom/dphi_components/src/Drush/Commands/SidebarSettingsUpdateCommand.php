<?php

namespace Drupal\dphi_components\Drush\Commands;

use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;
use Drupal\Core\Batch\BatchBuilder;

class SidebarSettingsUpdateCommand extends DrushCommands {

  /**
   * Process all 'page' nodes using batch processing.
   *
   * @command dphi_components:update-sidebar-settings
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
   * Batch operation callback to process each node.
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

      if ($node) {
        $sidebarSetting = $node->get('field_sidebar_setting')->first();
        if (empty($sidebarSetting)) {
          $showSidebar = (bool) $node->get('field_show_left_side_navigation')->first()?->get('value')->getValue();
          if ($showSidebar) {
            $node->set('field_sidebar_setting', 'global');
            $node->set('field_sidebar_level', 1);
          }
          else {
            $node->set('field_sidebar_setting', 'hidden');
          }
          $node->save();
          $context['message'] = t('Processed node ID @nid', ['@nid' => $nid]);
        } else {
          $context['message'] = t('No change required for node ID @nid', ['@nid' => $nid]);
        }
      }

      // Update the sandbox data.
      $context['sandbox']['current']++;
      $context['finished'] = $context['sandbox']['current'] / $context['sandbox']['total'];

    }
  }

}
