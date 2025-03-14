<?php

namespace Drupal\Tests\autologout\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Config;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Test the Autologout ajax endpoints.
 *
 * @description Ensure the AJAX endpoints work as expected
 *
 * @group Autologout
 */
class AutologoutAjaxTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'system_test',
    'views',
    'user',
    'autologout',
    'menu_ui',
    'block',
  ];

  /**
   * User with admin rights.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $privilegedUser;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $moduleConfig;

  /**
   * SetUp() performs any pre-requisite tasks that need to happen.
   */
  public function setUp(): void {
    parent::setUp();
    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser([
      'access content',
      'administer site configuration',
      'access site reports',
      'access administration pages',
      'bypass node access',
      'administer content types',
      'administer nodes',
      'administer autologout',
      'change own logout threshold',
    ]);
    $this->drupalLogin($this->privilegedUser);

    // Make node page default.
    $this->config('system.site')->set('page.front', 'node')->save();

    $this->moduleConfig = $this->container
      ->get('config.factory')
      ->getEditable('autologout.settings');
  }

  /**
   * Tests ajax logout callbacks work as expected.
   */
  public function testAutologoutByAjax() {
    $this->moduleConfig->set('timeout', 100)->set('padding', 10)->save();

    // Check that the user can access the page after login.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);

    // Test the time remaining callback works as expected.
    $result = Json::decode($this->drupalGet('autologout_ajax_get_time_left'));
    self::assertEquals(
      'insert',
      $result[0]['command'],
      'autologout_ajax_get_time_left returns an insert command for adding the jstimer onto the page'
    );
    self::assertEquals(
      '#timer',
      $result[0]['selector'],
      'autologout_ajax_get_time_left specifies the #timer selector.'
    );

    $remainingTime = 0;
    if (
      !empty($result[1]['settings']['time']) &&
      is_int($result[1]['settings']['time'])
    ) {
      $remainingTime = $result[1]['settings']['time'];
    }

    self::assertTrue(
      $remainingTime > 0,
      'autologout_ajax_get_time_left returns the remaining time as a positive integer'
    );

    // Test that ajax logout works as expected.
    $this->drupalGet('autologout_ajax_logout');
    $this->assertSession()->statusCodeEquals(200);

    // Check we are now logged out.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);
    self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));

    // Check further get time remaining requests return access denied.
    $this->drupalGet('autologout_ajax_get_time_left');
    $this->assertSession()->statusCodeEquals(403);

    // Check further logout requests result in access denied.
    $this->drupalGet('autologout_ajax_logout');
    $this->assertSession()->statusCodeEquals(403);

  }

  /**
   * Tests ajax logout callbacks work as expected when new response is added.
   */
  public function testAutologoutAjaxWithResponseAdded(): void {
    $this->moduleConfig->set('timeout', 100)->set('padding', 10)->save();

    // Check that the user can access the page after login.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);

    // Add a "response" in the first place.
    $addResponse = [
      [
        'command' => 'insert',
        'data' => 'test',
        'message' => 'Test response.',
      ],
    ];
    // Test the time remaining callback works as expected.
    $result = Json::decode($this->drupalGet('autologout_ajax_get_time_left'));

    // Merge the added response with the real ajax response.
    $result = array_merge($addResponse, $result);

    // Test that settings timer is not in first place.
    self::assertNotEquals(
      '#timer',
      $result[0]['data'],
      'Test response.'
    );

    // Get the key of settings time and test the callback works as expected.
    $arrayKey = '';
    foreach ($result as $key => $value) {
      if ($value['command'] === 'settings') {
        $arrayKey = $key;
      }
    }
    self::assertTrue(
      $result[$arrayKey]['settings']['time'] > 0,
      'autologout_ajax_get_time_left returns the remaining time as a positive integer'
    );

    // Test that the ajax insert is now in key 1.
    self::assertEquals(
      'insert',
      $result[1]['command'],
      'autologout_ajax_get_time_left returns an insert command for adding the jstimer onto the page'
    );
    self::assertEquals(
      '#timer',
      $result[1]['selector'],
      'autologout_ajax_get_time_left specifies the #timer selector.'
    );

    // Test that ajax logout works as expected.
    $this->drupalGet('autologout_ajax_logout');
    $this->assertSession()->statusCodeEquals(200);

    // Check we are now logged out.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);
    self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));
  }

  /**
   * Tests ajax stay logged in callbacks work as expected.
   */
  public function testStayloggedInByAjax() {
    $this->moduleConfig->set('timeout', 20)->set('padding', 5)->save();

    // Check that the user can access the page after login.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);
    self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

    // Sleep for half the timeout.
    sleep(14);

    // Test that ajax stay logged in works.
    $result = Json::decode($this->drupalGet('autologout_ajax_set_last'));
    $this->assertSession()->statusCodeEquals(200);
    self::assertEquals(
      'insert',
      $result[0]['command'],
      'autologout_ajax_set_last returns an insert command for adding the jstimer onto the page'
    );
    self::assertEquals(
      '#timer',
      $result[0]['selector'],
      'autologout_ajax_set_last specifies the #timer selector.'
    );

    // Sleep for half the timeout again.
    sleep(14);

    // Check we are still logged in.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);
    self::assertTrue($this->drupalUserIsLoggedIn($this->privilegedUser));

    // Logout.
    $this->drupalGet('autologout_ajax_logout');
    $this->assertSession()->statusCodeEquals(200);
    self::assertFalse($this->drupalUserIsLoggedIn($this->privilegedUser));

    // Check further requests to set last result in 403.
    $this->drupalGet('autologout_ajax_set_last');
    $this->assertSession()->statusCodeEquals(403);
  }

}
