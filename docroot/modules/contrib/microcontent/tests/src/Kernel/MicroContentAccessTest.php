<?php

namespace Drupal\Tests\microcontent\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\microcontent\Traits\MicroContentTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a class for testing access/permissions for micro-content entities.
 *
 * @group microcontent
 */
class MicroContentAccessTest extends KernelTestBase {

  use UserCreationTrait;
  use MicroContentTestTrait;

  /**
   * Test type.
   *
   * @var \Drupal\microcontent\Entity\MicroContentTypeInterface
   */
  protected $microContentType1;

  /**
   * Test type.
   *
   * @var \Drupal\microcontent\Entity\MicroContentTypeInterface
   */
  protected $microContentType2;

  /**
   * Test content.
   *
   * @var \Drupal\microcontent\Entity\MicroContentInterface
   */
  protected $microContent1;

  /**
   * Test content.
   *
   * @var \Drupal\microcontent\Entity\MicroContentInterface
   */
  protected $microContent2;

  /**
   * Test content.
   *
   * @var \Drupal\microcontent\Entity\MicroContentInterface
   */
  protected $microContent3;

  /**
   * Test content.
   *
   * @var \Drupal\microcontent\Entity\MicroContentInterface
   */
  protected $microContent4;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user3;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user4;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user5;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user6;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user7;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'microcontent',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('microcontent');
    $this->installEntitySchema('user');

    // Setup some test entities.
    $this->microContentType1 = $this->createMicroContentType('type1', 'name1');
    $this->microContentType1 = $this->createMicroContentType('type2', 'name2');
    // First user is user 1, has all permissions.
    $this->createUser();

    $this->user1 = $this->createUser([
      'update any type1 microcontent',
      'delete any type1 microcontent',
      'create type1 microcontent',
      'update own type2 microcontent',
      'delete own type2 microcontent',
      'access content',
    ]);

    $this->user2 = $this->createUser([
      'update any type2 microcontent',
      'delete any type2 microcontent',
      'create type2 microcontent',
      'update own type1 microcontent',
      'delete own type1 microcontent',
    ]);

    $this->user3 = $this->createUser();

    $this->user4 = $this->createUser([
      'delete own type1 microcontent',
    ]);

    $this->user5 = $this->createUser([
      'delete any type1 microcontent',
    ]);

    $this->user6 = $this->createUser([
      'update any type1 microcontent',
    ]);

    $this->user7 = $this->createUser([
      'administer microcontent',
    ]);

    $this->microContent1 = $this->createMicroContent([
      'type' => 'type1',
      'label' => 'content1',
      'uid' => $this->user2->id(),
      'status' => TRUE,
    ]);
    $this->microContent2 = $this->createMicroContent([
      'type' => 'type2',
      'label' => 'content2',
      'uid' => $this->user2->id(),
      'status' => TRUE,
    ]);
    $this->microContent3 = $this->createMicroContent([
      'type' => 'type2',
      'label' => 'content3',
      'uid' => $this->user1->id(),
      'status' => TRUE,
    ]);
    $this->microContent4 = $this->createMicroContent([
      'type' => 'type1',
      'label' => 'content4',
      'uid' => $this->user4->id(),
      'status' => TRUE,
    ]);
  }

  /**
   * Tests microcontent access.
   */
  public function testAccess() {
    $this->assertTrue($this->microContentType1->access('view label', $this->user1));
    // User has admin permission.
    $this->assertTrue($this->microContent1->access('update', $this->user7));
    // User has edit-any permission.
    $this->assertTrue($this->microContent1->access('update', $this->user1));
    // User has edit-own permission.
    $this->assertTrue($this->microContent1->access('update', $this->user2));
    // User has no permission.
    $this->assertFalse($this->microContent1->access('update', $this->user3));

    // User has admin permission.
    $this->assertTrue($this->microContent1->access('delete', $this->user7));
    // User has delete-any permission.
    $this->assertTrue($this->microContent1->access('delete', $this->user1));
    // User has delete-own permission.
    $this->assertTrue($this->microContent1->access('delete', $this->user2));
    // User has no permission.
    $this->assertFalse($this->microContent1->access('delete', $this->user3));

    // User has admin permission.
    $this->assertTrue($this->microContent2->access('update', $this->user7));
    // User has no permission.
    $this->assertFalse($this->microContent2->access('update', $this->user1));
    // User has edit-any permission.
    $this->assertTrue($this->microContent2->access('update', $this->user2));
    // User has edit-own permission but is not owner.
    $this->assertFalse($this->microContent2->access('update', $this->user1));
    // User has no permission.
    $this->assertFalse($this->microContent2->access('update', $this->user3));

    // User has admin permission.
    $this->assertTrue($this->microContent2->access('delete', $this->user7));
    // User has no permission.
    $this->assertFalse($this->microContent2->access('delete', $this->user1));
    // User has delete-any permission.
    $this->assertTrue($this->microContent2->access('delete', $this->user2));
    // User has delete-own permission but is not owner.
    $this->assertFalse($this->microContent2->access('delete', $this->user1));
    // User has no permission.
    $this->assertFalse($this->microContent2->access('delete', $this->user3));

    // User has admin permission.
    $this->assertTrue($this->microContent3->access('update', $this->user7));
    // User has edit-own permission.
    $this->assertTrue($this->microContent3->access('update', $this->user1));
    // User has edit-any permission.
    $this->assertTrue($this->microContent3->access('update', $this->user2));
    // User has no permission.
    $this->assertFalse($this->microContent3->access('update', $this->user3));

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $handler = $entity_type_manager->getAccessControlHandler('microcontent');

    // User has create access.
    $this->assertTrue($handler->createAccess('type1', $this->user1));
    $this->assertTrue($handler->createAccess('type2', $this->user2));
    $this->assertFalse($handler->createAccess('type1', $this->user2));
    $this->assertFalse($handler->createAccess('type2', $this->user1));
    $this->assertTrue($handler->createAccess('type1', $this->user7));
    $this->assertTrue($handler->createAccess('type2', $this->user7));

    // User has delete-own permission.
    $this->assertTrue($this->microContent4->access('delete', $this->user4));
    // User has delete-any.
    $this->assertTrue($this->microContent4->access('delete', $this->user5));
    // User has no permission.
    $this->assertFalse($this->microContent4->access('delete', $this->user2));
    $this->assertFalse($this->microContent4->access('delete', $this->user6));

    // Users shouldn't have access to any other operation.
    $this->assertFalse($this->microContent1->access('foo-op', $this->user1));
    $this->assertFalse($this->microContent1->access('foo-op', $this->user2));
    $this->assertFalse($this->microContent1->access('foo-op', $this->user3));
    $this->assertFalse($this->microContent1->access('foo-op', $this->user4));
    $this->assertFalse($this->microContent1->access('foo-op', $this->user5));
    $this->assertFalse($this->microContent1->access('foo-op', $this->user6));
  }

}
