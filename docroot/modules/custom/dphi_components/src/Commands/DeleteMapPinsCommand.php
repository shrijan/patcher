<?php

namespace Drupal\dphi_components\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a Drush command for deleting map pins.
 */
class DeleteMapPinsCommand extends DrushCommands
{

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager)
  {
    $this->entityTypeManager = $entityTypeManager;
    parent::__construct();
  }

  /**
   * Deletes all map_pin entities.
   *
   * @command dphi_components:delete_map_pins
   * @aliases dmp
   * @usage dphi_components:delete_map_pins
   *   Deletes all map_pin entities.
   */
  public function deleteMapPins()
  {
    $storage = $this->entityTypeManager->getStorage('map_pin');
    $ids = $storage->getQuery()->accessCheck(FALSE)->execute();

    if ($ids) {
      $entities = $storage->loadMultiple($ids);
      $storage->delete($entities);
      $this->logger()->success(dt('All map_pin entities have been deleted.'));
    } else {
      $this->logger()->warning(dt('No map_pin entities found to delete.'));
    }
  }
}
