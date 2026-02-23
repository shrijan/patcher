<?php

declare(strict_types=1);

namespace Drupal\dphi_components;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of map pin type entities.
 *
 * @see \Drupal\dphi_components\Entity\MapPinType
 */
final class MapPinTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No map pin types available. <a href=":link">Add map pin type</a>.',
      [':link' => Url::fromRoute('entity.map_pin_type.add_form')->toString()],
    );

    return $build;
  }

}
