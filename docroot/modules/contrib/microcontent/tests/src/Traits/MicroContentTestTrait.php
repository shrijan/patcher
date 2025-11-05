<?php

namespace Drupal\Tests\microcontent\Traits;

use Drupal\microcontent\Entity\MicroContent;
use Drupal\microcontent\Entity\MicroContentInterface;
use Drupal\microcontent\Entity\MicroContentType;
use Drupal\microcontent\Entity\MicroContentTypeInterface;

/**
 * Defines a micro-content test trait.
 */
trait MicroContentTestTrait {

  /**
   * Creates a micro-content type.
   *
   * @param string $id
   *   Type ID.
   * @param string $name
   *   Type name.
   * @param array $values
   *   Initial values.
   *
   * @return \Drupal\microcontent\Entity\MicroContentTypeInterface
   *   New micro-content type.
   */
  protected function createMicroContentType(string $id, string $name, array $values = []) : MicroContentTypeInterface {
    $values += [
      'id' => $id,
      'name' => $name,
    ];
    $type = MicroContentType::create($values);
    $type->save();
    if (method_exists($this, 'markEntityForCleanup')) {
      $this->markEntityForCleanup($type);
    }
    return $type;
  }

  /**
   * Creates micro-content.
   *
   * @param array $values
   *   Field values.
   *
   * @return \Drupal\microcontent\Entity\MicroContentInterface
   *   New micro-content entity.
   */
  protected function createMicroContent(array $values) : MicroContentInterface {
    $entity = MicroContent::create($values);
    $entity->save();
    if (method_exists($this, 'markEntityForCleanup')) {
      $this->markEntityForCleanup($entity);
    }
    return $entity;
  }

  /**
   * Creates a new revision for a given micro-content item.
   *
   * @param Drupal\microcontent\Entity\MicroContentInterface $microcontent
   *   A micro-content object.
   *
   * @return Drupal\microcontent\Entity\MicroContentInterface
   *   A micro-content object with up to date revision information.
   */
  protected function createMicroContentRevision(MicroContentInterface $microcontent) {
    $microcontent->set('label', $this->randomMachineName());
    $microcontent->setNewRevision();
    $microcontent->save();
    return $microcontent;
  }

}
