<?php

/**
 * @file
 * Microcontent module's post_update file.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\user\Entity\Role;
use Drupal\views\Entity\View;
use Drupal\views\ViewEntityInterface;

/**
 * Migrate "administer microcontent" to "access microcontent overview".
 */
function microcontent_post_update_access_overview_permission(&$sandbox = NULL) {
  foreach (Role::loadMultiple() as $role) {
    if ($role->hasPermission('administer microcontent')) {
      $role->revokePermission('administer microcontent');
      $role->grantPermission('access microcontent overview');
      $role->save();
    }
  }
}

/**
 * Update permission required for `micro_content_admin` view if it exists.
 */
function microcontent_post_update_admin_view_permission2(&$sandbox = NULL) {
  $view = View::load('micro_content_admin');
  if (!$view instanceof ViewEntityInterface) {
    return;
  }

  $display =& $view->getDisplay('default');
  if (($display['display_options']['access']['type'] ?? NULL) !== 'perm'
    || ($display['display_options']['access']['options']['perm'] ?? NULL) !== 'administer microcontent') {
    return;
  }

  $display['display_options']['access'] = [
    'type' => 'perm',
    'options' => ['perm' => 'access microcontent overview'],
  ];

  $view->save();
}

/**
 * Re-save MicroContentType configurations with new_revision config.
 */
function microcontent_post_update_set_new_revision(&$sandbox = NULL): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'microcontent_type', function () {
      return TRUE;
    });
}
