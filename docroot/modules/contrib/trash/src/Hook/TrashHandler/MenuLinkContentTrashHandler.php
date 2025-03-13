<?php

declare(strict_types=1);

namespace Drupal\trash\Hook\TrashHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\trash\Handler\DefaultTrashHandler;

/**
 * Provides a trash handler for the 'menu_link_content' entity type.
 */
class MenuLinkContentTrashHandler extends DefaultTrashHandler {

  public function __construct(
    protected MenuLinkManagerInterface $menuLinkManager,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_update() for 'menu_link_content'.
   */
  #[Hook('menu_link_content_update')]
  public function entityUpdate(EntityInterface $entity): void {
    assert($entity instanceof MenuLinkContentInterface);

    // Handle removing menu link definitions. It's essential that this is done
    // in an update hook rather than a presave one because it needs to run after
    // \Drupal\menu_link_content\Entity\MenuLinkContent::postSave(). That method
    // might add a deleted menu link to the Live menu tree when a workspace is
    // published, so this code needs to run afterward in order to remove it
    // again.
    if (trash_entity_is_deleted($entity)) {
      $this->menuLinkManager->removeDefinition($entity->getPluginId(), FALSE);
    }
  }

}
