<?php

namespace Drupal\microcontent\EntityHandlers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a list-builder for micro-content-entities.
 */
class MicrocontentTypeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'name' => $this->t('Name'),
      'description' => $this->t('Description'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    return [
      'name' => $entity->toLink($entity->label(), 'edit-form'),
      'description' => $entity->getDescription(),
    ] + parent::buildRow($entity);
  }

}
