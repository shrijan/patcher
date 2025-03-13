<?php

namespace Drupal\Tests\microcontent\Functional;

/**
 * Tests the revisionability of micro-content entities.
 *
 * @group microcontent
 */
class MicroContentRevisionTest extends MicroContentFunctionalTestBase {

  /**
   * Tests default revision settings on microcontent types.
   */
  public function testMicroContentRevision(): void {
    $assert = $this->assertSession();
    $this->createMicroContentType('type1', 'Type1', ['new_revision' => TRUE]);
    $this->createMicroContentType('type2', 'Type2', ['new_revision' => FALSE]);
    $user = $this->createUser([
      'update any type1 microcontent',
      'delete any type1 microcontent',
      'create type1 microcontent',
      'update any type2 microcontent',
      'delete any type2 microcontent',
      'create type2 microcontent',
      'access content',
    ]);
    $microContent1 = $this->createMicroContent([
      'type' => 'type1',
      'label' => 'content1',
    ]);
    $microContent2 = $this->createMicroContent([
      'type' => 'type2',
      'label' => 'content2',
    ]);
    // Create some revisions so revision checkbox is visible.
    $microContent1 = $this->createMicroContentRevision($microContent1);
    $microContent2 = $this->createMicroContentRevision($microContent2);
    $this->drupalLogin($user);
    $this->drupalGet($microContent1->toUrl('edit-form'));
    $assert->statusCodeEquals(200);
    $assert->checkboxChecked('Create new revision');
    $this->drupalGet($microContent2->toUrl('edit-form'));
    $assert->statusCodeEquals(200);
    $assert->checkboxNotChecked('Create new revision');
  }

}
