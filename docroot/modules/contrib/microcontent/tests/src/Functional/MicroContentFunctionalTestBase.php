<?php

namespace Drupal\Tests\microcontent\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\microcontent\Traits\MicroContentTestTrait;

/**
 * Defines a class for testing micro-content administration.
 *
 * @group microcontent
 */
abstract class MicroContentFunctionalTestBase extends BrowserTestBase {

  use MicroContentTestTrait;

  /**
   * User interface.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'microcontent',
    'block',
    'field_ui',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->createUser([
      'access microcontent overview',
      'administer microcontent types',
      'administer microcontent',
      'access administration pages',
    ]);
    $this->drupalPlaceBlock('local_actions_block');
  }

}
