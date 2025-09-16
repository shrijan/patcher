<?php

namespace Drupal\dphi_components;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the map pin entity type.
 */
class MapPinAccessControlHandler extends EntityAccessControlHandler
{

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
  {

    assert($entity instanceof MapPinInterface);

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view map pin');

      case 'update':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['edit map pin', 'administer map pin'],
          'OR',
        );

      case 'delete':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['delete map pin', 'administer map pin'],
          'OR',
        );

      case 'view all revisions':
        return AccessResult::allowedIfHasPermission($account, 'view map pin revisions');

      case 'view revision':
        return AccessResult::allowedIfHasPermission($account, 'view map pin revisions');

      case 'revert':
        return AccessResult::allowedIfHasPermission($account, 'revert map pin revisions');

      case 'delete revision':
        return AccessResult::allowedIfHasPermission($account, 'delete map pin revisions');

      default:
        return parent::checkAccess($entity, $operation, $account);
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
  {
    return AccessResult::allowedIfHasPermissions(
      $account,
      ['create map pin', 'administer map pin'],
      'OR',
    );
  }

}
