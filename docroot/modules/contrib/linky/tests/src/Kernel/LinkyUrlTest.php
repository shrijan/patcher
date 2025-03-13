<?php

namespace Drupal\Tests\linky\Kernel;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\linky\Entity\Linky;
use Drupal\linky\Url;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests Linky URL.
 *
 * @group linky
 * @coversDefaultClass \Drupal\linky\Url
 */
class LinkyUrlTest extends LinkyKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
  ];

  /**
   * Tests getting internal path from internal Linkys.
   */
  public function testInternal() {
    $this->installEntitySchema('entity_test');
    $this->setUpCurrentUser();

    $entity = EntityTest::create();
    $entity->save();
    $entityId = $entity->id();

    $link = Linky::create([
      'link' => [
        'uri' => 'internal:/entity_test/' . $entityId,
      ],
    ]);
    $link->save();

    $url = $link->toUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertEquals('admin/content/linky/' . $link->id(), $url->getInternalPath());
    $this->assertEquals('/entity_test/' . $entityId, $url->toString());
  }

  /**
   * Tests getting internal path from external Linkys.
   *
   * Without the special Url class, an exception would be thrown:
   * UnexpectedValueException Unrouted URIs do not have internal
   * representations.
   */
  public function testExternal() {
    $link = Linky::create([
      'link' => [
        'uri' => 'http://hello.world/kapoww',
      ],
    ]);
    $link->save();

    $url = $link->toUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertEquals('admin/content/linky/' . $link->id(), $url->getInternalPath());
    $this->assertEquals('http://hello.world/kapoww', $url->toString());
  }

  /**
   * Malformed URL throws correct exception.
   *
   * \Drupal\linky\Entity\Linky::toUrl calls \Drupal\Core\Url::fromUri which
   * throws exceptions, make sure fromUri exceptions are the same exceptions
   * that EntityInterface::toUrl is permitted to throw.
   */
  public function testMalformedUrlException() {
    $this->expectException(EntityMalformedException::class);
    $this->expectExceptionMessage("The URI 'http://hello: world' is malformed.");
    // Malformed URL.
    $link = Linky::create([
      'link' => [
        'uri' => 'http://hello: world',
      ],
    ]);
    $link->save();
    // Must be canonical.
    $link->toUrl('canonical');
  }

}
