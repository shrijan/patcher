<?php

namespace Drupal\Tests\linky\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines a class for testing linky functionality.
 *
 * @group linky
 */
class LinkyFunctionalTest extends BrowserTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * A user that can only edit their own linkys.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $linkyOwnEditor;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'linky',
    'user',
    'dynamic_entity_reference',
    'field_ui',
    'entity_test',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test display',
    'administer entity_test form display',
    'administer entity_test content',
    'add linky entities',
    'edit linky entities',
    'delete linky entities',
    'view linky entities',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    // Test admin user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->linkyOwnEditor = $this->drupalCreateUser([
      'edit own linky entities',
      'delete own linky entities',
    ]);
  }

  /**
   * Tests admin UI.
   */
  public function testLinkyAdminUi() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content/linky/add');
    // We test with an external URL to ensure that view builder can render the
    // entity.
    $url = 'http://example.com/test';
    $this->submitForm([
      'link[0][uri]' => $url,
      'link[0][title]' => 'Test',
    ], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($url);
    $entity = $this->getMostRecentlyCreatedLinky();
    $edit_url = $entity->toUrl('edit-form');
    $delete_url = $entity->toUrl('delete-form');
    $this->drupalGet($edit_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($delete_url);
    $this->assertSession()->statusCodeEquals(200);

    // Test the edit & delete own permission.
    $this->drupalLogin($this->linkyOwnEditor);
    $this->drupalGet($edit_url);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($delete_url);
    $this->assertSession()->statusCodeEquals(403);
    $entity->setOwner($this->linkyOwnEditor)->save();
    $this->drupalGet($edit_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($delete_url);
    $this->assertSession()->statusCodeEquals(200);
    // Test the admin user can edit & delete other user's linky entities.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($edit_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($delete_url);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Gets the most recently created linky entity.
   *
   * @return \Drupal\linky\LinkyInterface|null
   *   The linky entity or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMostRecentlyCreatedLinky() {
    $results = \Drupal::entityQuery('linky')
      ->sort('id', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    $id = array_shift($results);
    return \Drupal::entityTypeManager()->getStorage('linky')->load($id);
  }

}
