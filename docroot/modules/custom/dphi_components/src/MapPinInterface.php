<?php

namespace Drupal\dphi_components;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a map pin entity type.
 */
interface MapPinInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
