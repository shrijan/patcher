<?php

declare(strict_types=1);

namespace Drupal\Tests\bca\Kernel;

use Drupal\bca_annotation_test\Entity\BcaTestBundle;
use Drupal\bca_annotation_test\Entity\BcaTestBundleWithLabel;
use Drupal\bca_annotation_test\Entity\BcaUser;
use Drupal\bca_annotation_test\Entity\EntityTest\BcaSubdirTestBundle;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests entity bundles with annotations.
 *
 * @group bca
 */
class EntityBundleAnnotationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
    'bca',
    'bca_annotation_test',
  ];

  /**
   * Test bundle class defined with annotations.
   */
  public function testBundleClassAnnotations(): void {
    $bundleInfo = $this->container->get('entity_type.bundle.info');

    entity_test_create_bundle('bca_test_bundle', 'BCA Test Bundle');
    $entity = EntityTest::create(['type' => 'bca_test_bundle']);
    $this->assertInstanceOf(BcaTestBundle::class, $entity);

    $label = $bundleInfo->getBundleInfo('entity_test')['bca_test_bundle']['label'];
    $this->assertEquals('BCA Test Bundle', $label);

    entity_test_create_bundle('bca_test_bundle_with_label', 'BCA Test Bundle with Label');
    $entity = EntityTest::create(['type' => 'bca_test_bundle_with_label']);
    $this->assertInstanceOf(BcaTestBundleWithLabel::class, $entity);

    $label = $bundleInfo->getBundleInfo('entity_test')['bca_test_bundle_with_label']['label'];
    $this->assertEquals('Overridden label', $label);

    entity_test_create_bundle('bca_subdir_test_bundle');
    $entity = EntityTest::create(['type' => 'bca_subdir_test_bundle']);
    $this->assertInstanceOf(BcaSubdirTestBundle::class, $entity);

    $user = User::create();
    $this->assertInstanceOf(BcaUser::class, $user);
  }

}
