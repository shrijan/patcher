<?php

namespace Drupal\linky;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Linky entity.
 *
 * @see \Drupal\linky\Entity\Linky.
 */
class LinkyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $is_owner = ($account->id() && $account->id() === $entity->getOwnerId());

    /** @var \Drupal\linky\LinkyInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view linky entities');

      case 'update':
        if ($account->hasPermission('edit linky entities')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('edit own linky entities') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'edit linky entities' OR 'edit own linky entities'.")->cachePerUser();

      case 'delete':
        if ($account->hasPermission('delete linky entities')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('delete own linky entities') && $is_owner) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'delete linky entities' OR 'delete own linky entities'.")->cachePerUser();

      case 'view all revisions':
        return AccessResult::allowedIfHasPermission($account, 'view any linky history');

      case 'view revision':
        return AccessResult::allowedIfHasPermission($account, 'view any linky revisions');

      case 'revert':
        return AccessResult::allowedIfHasPermission($account, 'revert any linky revisions');

      case 'delete revision':
        return AccessResult::allowedIfHasPermission($account, 'delete any linky revisions');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add linky entities');
  }

}
