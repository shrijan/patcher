<?php

namespace Drupal\Tests\linky\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test base for linky kernel tests.
 */
abstract class LinkyKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'linky',
    'link',
    'dynamic_entity_reference',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('linky');
  }

}
