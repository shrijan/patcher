<?php

namespace Drupal\dphi_components\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.menu.add_form')) {
      $route->setRequirement('_role', 'administrator+site_admin');
    }
    if ($route = $collection->get('entity.menu.add_link_form')) {
      $route->setRequirement('_custom_access', '\Drupal\dphi_components\Controller\MenuController::access');
    }
    if ($route = $collection->get('entity.menu.edit_form')) {
      $route->setRequirement('_custom_access', '\Drupal\dphi_components\Controller\MenuController::access');
    }
  }
}
