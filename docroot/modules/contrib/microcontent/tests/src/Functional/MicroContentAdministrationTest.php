<?php

namespace Drupal\Tests\microcontent\Functional;

use Drupal\Core\Url;
use Drupal\microcontent\Entity\MicroContentType;
use Drupal\microcontent\Entity\MicroContentTypeInterface;

/**
 * Defines a class for testing micro-content administration.
 *
 * @group microcontent
 */
class MicroContentAdministrationTest extends MicroContentFunctionalTestBase {

  /**
   * Tests local tabs display for microcontent.
   */
  public function testMicroContentLocalTabs() {
    $assert = $this->assertSession();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->createMicroContentType('type1', 'name1');
    $user = $this->createUser([
      'update any type1 microcontent',
      'delete any type1 microcontent',
      'create type1 microcontent',
      'access content',
    ]);
    $microContent1 = $this->createMicroContent([
      'type' => 'type1',
      'label' => 'content1',
      'revision_uid' => $user->id(),
      'status' => TRUE,
    ]);
    $this->drupalLogin($user);
    $this->drupalGet($microContent1->toUrl());
    $assert->linkExists('Edit');
    $assert->linkExists('Delete');
  }

  /**
   * Tests micro-content administration.
   */
  public function testMicroContentTypeAdministration() {
    $this->assertThatAnonymousUserCannotAdministerMicroContentTypes();
    $type = $this->assertThatAdminCanAddMicroContentTypes();
    $type = $this->assertThatAdminCanEditMicroContentTypes($type);
    $this->assertThatAdminCanDeleteMicroContentTypes($type);
    $this->assertThatAdminCanViewMicrocontentList();
  }

  /**
   * Assert that admin can add a micro-content type.
   *
   * @return \Drupal\microcontent\Entity\MicroContentTypeInterface
   *   The added type.
   */
  private function assertThatAdminCanAddMicroContentTypes() : MicroContentTypeInterface {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('system.admin_structure'));
    $assert = $this->assertSession();
    $assert->linkExists('Micro-content types');
    $this->drupalGet(Url::fromRoute('entity.microcontent_type.collection'));
    $assert->statusCodeEquals(200);
    $assert->linkExists('Add micro-content type');
    $this->clickLink('Add micro-content type');
    $this->assertStringContainsString(Url::fromRoute('entity.microcontent_type.add_form')->toString(), $this->getSession()->getCurrentUrl());
    $type_name = $this->randomMachineName();
    $id = mb_strtolower($this->randomMachineName());
    $this->submitForm([
      'id' => $id,
      'name' => $type_name,
      'description' => $this->randomString(),
      'type_class' => $this->randomString(),
    ], 'Save');
    $assert->pageTextContains(sprintf('Micro-content type %s has been added.', $type_name));
    $assert->linkExists($type_name);

    $this->drupalGet(Url::fromRoute('entity.microcontent.add_page'));
    $assert->pageTextNotContains('There is no micro-content type yet');
    return MicroContentType::load($id);
  }

  /**
   * Tests anonymous users can't access type admin routes.
   */
  private function assertThatAnonymousUserCannotAdministerMicroContentTypes() : void {
    $type = $this->createMicroContentType($this->randomMachineName(), $this->randomMachineName());
    $urls = [
      Url::fromRoute('entity.microcontent_type.collection'),
      $type->toUrl('edit-form'),
      $type->toUrl('delete-form'),
      Url::fromRoute('entity.microcontent.collection'),
    ];
    foreach ($urls as $url) {
      $this->drupalGet($url);
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Assert that admin can edit types.
   *
   * @param \Drupal\microcontent\Entity\MicroContentTypeInterface $type
   *   Type to edit.
   *
   * @return \Drupal\microcontent\Entity\MicroContentTypeInterface
   *   The edited type.
   */
  private function assertThatAdminCanEditMicroContentTypes(MicroContentTypeInterface $type) : MicroContentTypeInterface {
    $this->drupalGet(Url::fromRoute('entity.microcontent_type.collection'));
    $assert = $this->assertSession();
    $edit = $type->toUrl('edit-form');
    $assert->linkByHrefExists($edit->toString());
    $this->drupalGet($edit);
    $assert->fieldValueEquals('name', $type->label());
    $assert->fieldValueEquals('description', $type->getDescription());
    $assert->fieldValueEquals('type_class', $type->getTypeClass());
    $new_name = $this->randomMachineName();
    $this->submitForm([
      'name' => $new_name,
    ], 'Save');
    $assert->pageTextContains(sprintf('Micro-content type %s has been updated.', $new_name));
    return \Drupal::entityTypeManager()->getStorage('microcontent_type')->loadUnchanged($type->id());
  }

  /**
   * Assert that admin can delete micro-content types.
   *
   * @param \Drupal\microcontent\Entity\MicroContentTypeInterface $type
   *   The type to delete.
   */
  private function assertThatAdminCanDeleteMicroContentTypes(MicroContentTypeInterface $type) : void {
    $this->drupalGet(Url::fromRoute('entity.microcontent_type.collection'));
    $assert = $this->assertSession();
    $delete = $type->toUrl('delete-form');
    $assert->linkByHrefExists($delete->toString());
    $this->drupalGet($delete);
    $this->submitForm([], 'Delete');
    $assert->pageTextContains(sprintf('The micro-content type %s has been deleted.', $type->label()));
  }

  /**
   * Tests that admin can view micro-content list.
   */
  private function assertThatAdminCanViewMicrocontentList() : void {
    $this->drupalGet(Url::fromRoute('entity.microcontent.collection'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->linkExists('Add micro-content');
  }

}
