<?php

namespace Drupal\dphi_components\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

class MenuController {

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $menu) {
    if (in_array($menu, ['admin', 'tools', 'account']) && in_array('editor', $account->getRoles())) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }
}
