<?php

declare(strict_types=1);

namespace Drupal\Tests\preview_link\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * @covers \Drupal\preview_link\Form\PreviewLinkSettingsForm
 *
 * @group preview_link
 */
class PreviewLinkSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'preview_link',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The account used for logging into admin and running test.
   */
  protected ?AccountInterface $account;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    entity_test_create_bundle('bundle_a', 'Test bundle A', 'entity_test_rev');
    entity_test_create_bundle('bundle_b', 'Test bundle A', 'entity_test_rev');

    $this->account = $this->drupalCreateUser(['administer preview link settings']);
  }

  /**
   * Tests the preview link settings form.
   */
  public function testSettingsForm(): void {
    $assert_session = $this->assertSession();
    $url = Url::fromRoute('preview_link.settings');

    $this->drupalGet($url);
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->account);
    $this->drupalGet($url);

    $page = $this->getSession()->getPage();
    $page->selectFieldOption('multiple_entities', '1');
    $page->pressButton('Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('The configuration options have been saved.');
    $assert_session->fieldValueEquals('multiple_entities', '1');

    $page->selectFieldOption('enabled_entity_types[entity_test_rev]', 'entity_test_rev');
    $page->pressButton('Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('The configuration options have been saved.');
    $assert_session->fieldValueEquals('enabled_entity_types[entity_test_rev]', 'entity_test_rev');

    $config = $this->config('preview_link.settings')->get('enabled_entity_types');
    $this->assertEquals([
      'entity_test_rev' => [],
    ], $config);

    $page->selectFieldOption('enabled_entity_types[entity_test_rev:bundle_a]', 'entity_test_rev:bundle_a');
    $page->pressButton('Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('The configuration options have been saved.');
    $assert_session->fieldValueEquals('enabled_entity_types[entity_test_rev]', 'entity_test_rev');
    $assert_session->fieldValueEquals('enabled_entity_types[entity_test_rev:bundle_a]', 'entity_test_rev:bundle_a');

    // Need to clear config cache to update it and test again.
    $this->container->get('cache.config')->deleteAll();
    $this->container->get('config.factory')->reset('preview_link.settings');
    $config = $this->config('preview_link.settings')->get('enabled_entity_types');
    $this->assertEquals([
      'entity_test_rev' => [
        'bundle_a',
      ],
    ], $config);
  }

}
