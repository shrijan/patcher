<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Functional;

use Drupal\Core\Url;

/**
 * Test if layout builder can be accessed.
 *
 * @group scheduler_content_moderation_integration
 *
 * @see https://www.drupal.org/project/scheduler_content_moderation_integration/issues/3048485
 */
class LayoutBuilderTest extends SchedulerContentModerationBrowserTestBase {

  /**
   * Additional modules required for this test.
   *
   * @var array
   */
  protected static $modules = ['layout_builder', 'field_ui'];

  /**
   * Tests layout builder.
   */
  public function testLayoutBuilder() {
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'access content',
      'administer node display',
    ]));

    $path = 'admin/structure/types/manage/page/display/default';
    $this->drupalGet($path);

    $page = $this->getSession()->getPage();
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);

    // The next url is /admin/structure/types/manage/page/display/default/layout
    //
    // In tests on Drupal 9 this often (but not always) results in a status code
    // of 500 (internal server error). The generated html shows the error:
    // Drupal\Core\Entity\EntityStorageException: No entity bundle was specified
    // in Drupal\Core\Entity\ContentEntityStorageBase->createWithSampleValues()
    //
    // The same error is produced if instead of the drupalGet we use
    // $page->pressButton('Manage layout') or $page->clickLink('Manage layout')
    // Therefore stop the test here for earlier Core versions.
    // @see https://www.drupal.org/project/scheduler_content_moderation_integration/issues/3502119
    if (version_compare(\Drupal::VERSION, '10', '<')) {
      return;
    }

    $this->drupalGet(Url::fromRoute('layout_builder.defaults.node.view', [
      'node_type' => 'page',
      'view_mode_name' => 'default',
    ]));
    $this->assertSession()->statusCodeEquals(200);
  }

}
