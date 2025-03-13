<?php

namespace Drupal\Tests\microcontent\Functional;

use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests the micro-content moderation handler.
 *
 * @group microcontent
 */
class MicroContentModerationTest extends MicroContentFunctionalTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createMicroContentType('type1', 'Type1', ['new_revision' => TRUE]);
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('microcontent', 'type1');
    $workflow->save();
  }

  /**
   * Tests the moderation handler is disabling the create revision checkbox.
   */
  public function testMicroContentModerationHandler(): void {
    $assert = $this->assertSession();
    $user = $this->createUser([
      'update any type1 microcontent',
      'delete any type1 microcontent',
      'create type1 microcontent',
      'use editorial transition create_new_draft',
      'access content',
    ]);
    $microContent1 = $this->createMicroContent([
      'type' => 'type1',
      'label' => 'content1',
    ]);
    // Create a revision so revision checkbox is visible.
    $microContent1 = $this->createMicroContentRevision($microContent1);
    $this->drupalLogin($user);
    $this->drupalGet($microContent1->toUrl('edit-form'));
    $assert->statusCodeEquals(200);
    $assert->checkboxChecked('Create new revision');
    $this->assertSession()->fieldDisabled('revision');
    $this->assertSession()->pageTextContains('Revisions are required.');
  }

}
