<?php

namespace Drupal\microcontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Defines an interface for micro-content types.
 */
interface MicroContentTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Gets the type description.
   *
   * @return string
   *   Description.
   */
  public function getDescription() : string;

  /**
   * Gets the type class.
   *
   * @return string
   *   Class to apply to aid content-editors.
   */
  public function getTypeClass() : string;

  /**
   * Sets whether new revisions should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if items of this type should create new revisions by default.
   *
   * @return $this
   */
  public function setNewRevision($new_revision);

}
