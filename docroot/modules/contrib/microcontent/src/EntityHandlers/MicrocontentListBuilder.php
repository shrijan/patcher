<?php

namespace Drupal\microcontent\EntityHandlers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a list-builder for micro-content entities.
 */
class MicrocontentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Label'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    return [
      'label' => $entity->toLink($entity->label(), 'edit-form'),
    ] + parent::buildRow($entity);
  }

}
