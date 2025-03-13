<?php

namespace Drupal\Tests\linky\Kernel;

use Drupal\Core\Config\Config;
use Drupal\linky\Entity\Linky;

/**
 * A class to test the linky link constraint.
 *
 * @group linky
 */
class LinkyLinkConstraintTest extends LinkyKernelTestBase {

  private string $emailUri = 'mailto:blah.com';
  private string $telephoneUri = 'tel:012345';
  private string $invalidUri = 'http:blah.com';

  /**
   * A method to get the editable config for linky settings.
   */
  private function getEditableConfig(): Config {
    return \Drupal::configFactory()->getEditable('linky.settings');
  }

  /**
   * A method to update the linky additional schemes settings.
   *
   * @param string $setting
   *   The setting.
   * @param int $state
   *   The state.
   */
  private function updateAdditionalSchemesSetting(string $setting, int $state): void {
    $this->getEditableConfig()->set("additional_schemes.{$setting}", $state)->save();
  }

  /**
   * A method to test the validation.
   */
  public function testValidation(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $this->updateAdditionalSchemesSetting('email', 1);
    $this->updateAdditionalSchemesSetting('telephone', 1);
    $entity_field_manager->clearCachedFieldDefinitions();
    $this->assertEmailAllowed();
    $this->assertTelephoneAllowed();
    $this->updateAdditionalSchemesSetting('email', 0);
    $this->updateAdditionalSchemesSetting('telephone', 0);
    $entity_field_manager->clearCachedFieldDefinitions();
    $this->assertEmailDisallowed();
    $this->assertTelephoneDisallowed();
    $this->assertInvalid();
  }

  /**
   * A method to assert an allowed email uri.
   */
  private function assertEmailAllowed(): void {
    $link = Linky::create([
      'link' => [
        'uri' => $this->emailUri,
      ],
    ]);
    /** @var \Drupal\Core\Entity\EntityConstraintViolationList $errors */
    $errors = $link->validate();
    $this->assertCount(0, $errors);
  }

  /**
   * A method to assert an disallowed email uri.
   */
  private function assertEmailDisallowed(): void {
    $link = Linky::create([
      'link' => [
        'uri' => $this->emailUri,
      ],
    ]);
    /** @var \Drupal\Core\Entity\EntityConstraintViolationList $errors */
    $errors = $link->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals(\sprintf("The link %s' is not supported.", $this->emailUri), (string) $errors[0]->getMessage());
    $this->assertEquals('link.0.uri', $errors[0]->getPropertyPath());
  }

  /**
   * A method to assert an allowed telephone uri.
   */
  private function assertTelephoneAllowed(): void {
    $link = Linky::create([
      'link' => [
        'uri' => $this->telephoneUri,
      ],
    ]);
    /** @var \Drupal\Core\Entity\EntityConstraintViolationList $errors */
    $errors = $link->validate();
    $this->assertCount(0, $errors);
  }

  /**
   * A method to assert an disallowed telephone uri.
   */
  private function assertTelephoneDisallowed(): void {
    $link = Linky::create([
      'link' => [
        'uri' => $this->telephoneUri,
      ],
    ]);
    /** @var \Drupal\Core\Entity\EntityConstraintViolationList $errors */
    $errors = $link->validate();
    $this->assertCount(1, $errors);
    /** @var \Drupal\linky\Plugin\Validation\Constraint\LinkyLinkConstraint $constraint */
    $this->assertEquals(\sprintf("The link %s' is not supported.", $this->telephoneUri), (string) $errors[0]->getMessage());
    $this->assertEquals('link.0.uri', $errors[0]->getPropertyPath());
  }

  /**
   * A method to assert an invalid uri.
   */
  private function assertInvalid(): void {
    $link = Linky::create([
      'link' => [
        'uri' => $this->invalidUri,
      ],
    ]);
    /** @var \Drupal\Core\Entity\EntityConstraintViolationList $errors */
    $errors = $link->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals(\sprintf("The link %s' is invalid.", $this->invalidUri), (string) $errors[0]->getMessage());
    $this->assertEquals('link.0.uri', $errors[0]->getPropertyPath());
  }

}
