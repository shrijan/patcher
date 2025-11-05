<?php

namespace Drupal\microcontent\EntityHandlers;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a class for an access handler for types.
 */
class MicrocontentTypeAccessHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view label') {
      return AccessResult::allowedIfHasPermission($account, 'access content');
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
