<?php

namespace Drupal\microcontent\EntityHandlers;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Defines a micro-content route builder.
 */
class RouteBuilder extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    return parent::getEditFormRoute($entity_type);
  }

}
