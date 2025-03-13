<?php

namespace Drupal\file_rename\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;

/**
 * Determines access to renaming a file.
 */
class FileRenameAccessCheck implements AccessInterface {

  /**
   * Checks access for renaming file.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\file\FileInterface $file
   *   The file to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, FileInterface $file) {
    return AccessResult::allowedIf($account->hasPermission('rename files') && $file->isPermanent());
  }

}
