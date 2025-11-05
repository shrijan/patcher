<?php

namespace Drupal\microcontent\EntityHandlers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Defines a class for a microcontent view builder.
 */
class MicrocontentViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function addContextualLinks(array &$build, EntityInterface $entity) {
    /** @var \Drupal\microcontent\Entity\MicroContentInterface $entity */
    if ($entity->isNew()) {
      return;
    }
    $key = 'microcontent';
    if (!$entity->isDefaultRevision()) {
      $key .= '_revision';
    }
    $build['#contextual_links'][$key] = [
      'route_parameters' => ['microcontent' => $entity->id()],
      'metadata' => [
        'changed' => $entity->getChangedTime(),
      ],
    ];
  }

}
