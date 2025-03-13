<?php

namespace Drupal\microcontent\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a class for micro-content entity access.
 */
class MicroContentAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $microcontent, $operation, AccountInterface $account) {
    /** @var \Drupal\microcontent\Entity\MicroContentInterface $microcontent */
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    $user_is_owner = ($account->isAuthenticated() && $account->id() === $microcontent->getOwnerId());

    // Check if published or can view unpublished micro-content.
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account,
          'view unpublished microcontent')
          ->cachePerPermissions()
          ->orIf(AccessResult::allowedIf($microcontent->isPublished())
            ->addCacheableDependency($microcontent)
          );

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, sprintf('delete any %s microcontent', $microcontent->bundle()))
          ->orIf(AccessResult::allowedIf($user_is_owner && $account->hasPermission(sprintf('delete own %s microcontent', $microcontent->bundle())))
            ->cachePerPermissions()
            ->cachePerUser());

      case 'update':
        return AccessResult::allowedIfHasPermission($account, sprintf('update any %s microcontent', $microcontent->bundle()))
          ->orIf(AccessResult::allowedIf($user_is_owner && $account->hasPermission(sprintf('update own %s microcontent', $microcontent->bundle())))
            ->cachePerPermissions()
            ->cachePerUser());
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $admin_permission = $this->entityType->getAdminPermission();
    return AccessResult::allowedIfHasPermission($account, $admin_permission)->orIf(AccessResult::allowedIfHasPermission($account, 'create ' . $entity_bundle . ' microcontent'));
  }

}
