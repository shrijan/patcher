<?php

namespace Drupal\tinypng\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\tinypng\Controller\TinyPngImageStyleDownloadController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber service.
 *
 * @package Drupal\tinypng\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // This is not a good solution. Replace this if
    // https://www.drupal.org/project/drupal/issues/2940016 is closed.
    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get('image.style_public')) {
      $route->setDefault('_controller', TinyPngImageStyleDownloadController::class . '::deliver');
    }
    if ($route = $collection->get('image.style_private')) {
      $route->setDefault('_controller', TinyPngImageStyleDownloadController::class . '::deliver');
    }
  }

}
