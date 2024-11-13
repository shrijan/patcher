<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor_indentblock\Functional;

use Composer\Semver\Semver;
use Drupal\Tests\BrowserTestBase;

/**
 * Test ckeditor indentblock module.
 *
 * @group ckeditor_indentblock
 */
final class CKEditorIndentBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor_indentblock',
    'editor',
    'filter',
    'file',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $use_ck5 = Semver::satisfies(\Drupal::VERSION, '>=10');
    if ($use_ck5) {
      self::$modules[] = 'ckeditor5';
    }
    else {
      self::$modules[] = 'ckeditor';
    }
    parent::setUp();
  }

  /**
   * Test module installation.
   */
  public function testModuleInstall(): void {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('CKEditor IndentBlock');
  }

}
