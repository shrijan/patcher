<?php

namespace Drupal\Tests\microcontent\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\microcontent\Traits\MicroContentTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a class for testing micro-content entities.
 *
 * @group microcontent
 */
class MicroContentEntityTest extends KernelTestBase {

  use MicroContentTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'microcontent',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('microcontent');
    // User 1.
    $this->createUser();
  }

  /**
   * Tests micro-content entity and micro-content type.
   */
  public function testMicroContentEntity() {
    $type = $this->createMicroContentType('pane', 'Pane');
    $entity = $this->createMicroContent([
      'type' => $type->id(),
      'label' => 'New pane',
    ]);
    $entity->save();
    $this->assertEquals('New pane', $entity->label());
    $this->assertEquals('pane', $entity->bundle());
  }

  /**
   * Tests revision_user is set on initial save.
   */
  public function testMicroContentRevisionUser() {
    $user = $this->createUser();
    $this->setCurrentUser($user);
    $type = $this->createMicroContentType('foo', 'Foo');
    $entity = $this->createMicroContent([
      'type' => $type->id(),
      'label' => $this->randomMachineName(),
    ]);
    $entity->save();
    $this->assertEquals($user->id(), $entity->getRevisionUserId());
  }

}
