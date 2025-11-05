<?php

declare(strict_types=1);

namespace Drupal\bca\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines bundle attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Bundle extends Plugin {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    public readonly string $entityType,
    public ?string $bundle = NULL,
    public readonly ?TranslatableMarkup $label = NULL,
  ) {
    $this->bundle ??= $this->entityType;
    parent::__construct($this->entityType . ':' . $this->bundle);
  }

}
