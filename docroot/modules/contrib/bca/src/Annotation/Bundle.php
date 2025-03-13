<?php

declare(strict_types=1);

namespace Drupal\bca\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines bundle annotation object.
 *
 * @Annotation
 */
class Bundle extends Plugin {

  /**
   * The entity type.
   */
  public string $entity_type;

  /**
   * The bundle name.
   */
  public string $bundle;

  /**
   * The human-readable name of the bundle.
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * {@inheritdoc}
   */
  protected function parse(array $values): array {
    $values = parent::parse($values);
    $values['bundle'] ??= $values['entity_type'];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->definition['entity_type'] . ':' . $this->definition['bundle'];
  }

}
