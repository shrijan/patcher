<?php

namespace Drupal\microcontent\Plugin\BackFillQuery;

use Drupal\backfill_formatter\Plugin\BackFillQuery\PermissionStatusHandler;

/**
 * Defines a class for a microcontent query handler.
 *
 * @BackFillQuery(
 *   id = "default:microcontent",
 *   label = @Translation("Micro-content"),
 * )
 */
class MicroContentHandler extends PermissionStatusHandler {

  /**
   * {@inheritdoc}
   */
  protected function getPermission(): string {
    return 'view unpublished microcontent';
  }

}
