<?php

namespace Drupal\linky;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Linky entities.
 *
 * @ingroup linky
 */
class LinkyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['link'] = $this->t('Link');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['link'] = $entity->toLink($entity->label(), 'edit-form');
    return $row + parent::buildRow($entity);
  }

}
