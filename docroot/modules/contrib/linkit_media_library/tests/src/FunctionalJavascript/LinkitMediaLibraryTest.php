<?php

namespace Drupal\Tests\linkit_media_library\FunctionalJavascript;

use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\linkit\Entity\Profile;
use Drupal\linkit\Tests\ProfileCreationTrait;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\UserInterface;

/**
 * Tests Linkit Media Library integration.
 *
 * @group linkit_media_library
 */
class LinkitMediaLibraryTest extends WebDriverTestBase {

  use CKEditor5TestTrait;
  use MediaTypeCreationTrait;
  use ProfileCreationTrait;
  use TestFileCreationTrait;

  /**
   * Filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected FilterFormatInterface $filter;

  /**
   * Text editor config entity.
   *
   * @var \Drupal\editor\EditorInterface
   */
  protected EditorInterface $editor;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $testUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'linkit_media_library',
    'linkit_media_library_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();

    // Create text format, associate CKEditor 5, validate.
    $this->filter = FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'linkit' => [
          'status' => TRUE,
          'settings' => [
            'title' => FALSE,
          ],
        ],
      ],
    ]);
    $this->filter->save();

    $this->editor = Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [
            'link',
          ],
        ],
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => TRUE,
            'linkit_profile' => 'default',
          ],
        ],
      ],
    ]);
    $this->editor->save();

    // Create a 'document' media bundle.
    $this->createMediaType('file', ['id' => 'document']);

    // Create a test file and media entity.
    File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ])->save();
    Media::create([
      'bundle' => 'document',
      'name' => 'Test document',
      'field_media_document' => [
        [
          'target_id' => 1,
        ],
      ],
    ])->save();

    // Create test content type.
    $this->drupalCreateContentType(['type' => 'page']);

    // Create and login test user.
    $this->testUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
      'administer media',
    ]);
    $this->drupalLogin($this->testUser);
  }

  /**
   * Tests Media Library button rendering.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testButtonRendering(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('Link');

    // Verify linkit popup appears.
    $this->assertVisibleBalloon('.ck-link-form');
    $assert_session->elementExists('css', '.ck-link-form .linkit-ui-autocomplete');

    // Verify 'Media Library' button is rendered in linkit modal.
    $assert_session->elementExists('css', '.ck-link-form .ck-media-library');

    // Update editor settings to disable the Linkit CKEditor5 plugin.
    $this->editor->setSettings([
      'toolbar' => [
        'items' => [
          'link',
        ],
      ],
      'plugins' => [
        'linkit_extension' => [
          'linkit_enabled' => FALSE,
        ],
      ],
    ]);
    $this->editor->save();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('Link');

    // Verify linkit popup appears.
    $this->assertVisibleBalloon('.ck-link-form');
    $assert_session->elementNotExists('css', '.ck-link-form .linkit-ui-autocomplete');

    // Verify Media Linkit does not appear.
    $assert_session->elementNotExists('css', '.ck-link-form .ck-media-library');

    // Update editor settings to enable the Linkit CKEditor5 plugin.
    $this->editor->setSettings([
      'toolbar' => [
        'items' => [
          'link',
        ],
      ],
      'plugins' => [
        'linkit_extension' => [
          'linkit_enabled' => FALSE,
        ],
      ],
    ]);
    $this->editor->save();
    // Disable the Linkit filter.
    $this->filter->removeFilter('linkit');
    $this->filter->save();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('Link');

    // Verify linkit popup does not appear.
    $this->assertVisibleBalloon('.ck-link-form');
    $assert_session->elementNotExists('css', '.ck-link-form .linkit-ui-autocomplete');

    // Verify Media Linkit does not appear.
    $assert_session->elementNotExists('css', '.ck-link-form .ck-media-library');
  }

  /**
   * Tests that media links are correctly inserted into the editor.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testLinkitMediaLibraryInsertion(): void {
    $assert_session = $this->assertSession();

    $this->insertFromModal();

    $link = $assert_session->elementExists('css', 'a[data-entity-substitution="canonical"]');
    $this->assertNotEmpty($link);

    // Create a new Linkit profile that uses the test_substitution_asset
    // substitution plugin.
    $profile = Profile::create([
      'id' => 'test_profile',
      'label' => 'Test Linkit Profile',
      'description' => '',
      'matchers' => [
        'b8a294c6-a4c9-4c90-832c-ffc2ce1848df' => [
          'id' => 'entity:media',
          'uuid' => 'b8a294c6-a4c9-4c90-832c-ffc2ce1848df',
          'settings' => [
            'metadata' => '',
            'bundles' => [
              'document',
            ],
            'group_by_bundle' => FALSE,
            'substitution_type' => 'test_substitution_asset',
            'limit' => 100,
          ],
          'weight' => 0,
        ],
      ],
    ]);
    $profile->save();

    // Update editor settings to use the newly created Linkit profile.
    $this->editor->setSettings([
      'toolbar' => [
        'items' => [
          'link',
        ],
      ],
      'plugins' => [
        'linkit_extension' => [
          'linkit_enabled' => TRUE,
          'linkit_profile' => 'test_profile',
        ],
      ],
    ]);
    $this->editor->save();

    $this->insertFromModal();

    $link = $assert_session->elementExists('css', 'a[data-entity-substitution="test_substitution_asset"]');
    $this->assertNotEmpty($link);
  }

  /**
   * Inserts a test media entity from the link modal.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function insertFromModal(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('Link');

    // Verify linkit popup appears.
    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $assert_session->elementExists('css', '.ck-link-form .linkit-ui-autocomplete');

    // Verify 'Media Library' button is rendered in linkit modal.
    $assert_session->elementExists('css', '.ck-link-form .ck-media-library');

    $page->pressButton('Media Library');
    $assert_session->waitForElement('css', '.media-library-widget-modal');
    $page->checkField('Select Test document');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');

    $assert_session->waitForElementRemoved('css', '.media-library-widget-modal');
    // Verify link is correctly inserted.
    $balloon->pressButton('Save');
    $link = $assert_session->elementExists('css', 'a[href="/media/1"]');
    $this->assertNotEmpty($link);
  }

}
