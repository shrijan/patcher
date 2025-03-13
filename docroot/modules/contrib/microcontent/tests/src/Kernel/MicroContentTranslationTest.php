<?php

namespace Drupal\Tests\microcontent\Kernel;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\microcontent\Traits\MicroContentTestTrait;

/**
 * Tests microcontent entities translation support.
 *
 * @group microcontent
 */
class MicroContentTranslationTest extends KernelTestBase {

  use MicroContentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'microcontent',
    'user',
    'field',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['language']);
    $this->installEntitySchema('microcontent');

    $config_language_storage = $this->container->get('entity_type.manager')->getStorage('configurable_language');

    // Set up some dummy languages.
    for ($i = 0; $i < 3; ++$i) {
      $language_id = 'l' . $i;
      $config_language_storage->create([
        'id' => $language_id,
        'label' => $this->randomString(),
      ])->save();
    }
  }

  /**
   * Tests micro-content entities translation.
   */
  public function testMicroContentTranslation() {
    $type = $this->createMicroContentType('pane', 'Pane');
    $entity_type = $this->container->get('entity_type.manager')
      ->getDefinition('microcontent');
    $this->assertTrue($entity_type->isTranslatable(), 'Microcontent is translatable.');

    // Check if the translation handler uses the content_translation handler.
    $translation_handler_class = $entity_type->getHandlerClass('translation');
    $this->assertEquals(ContentTranslationHandler::class, $translation_handler_class, 'Translation handler is set to use the content_translation handler.');

    $microcontent_storage = $this->container->get('entity_type.manager')
      ->getStorage('microcontent');
    $microcontent = $microcontent_storage->create([
      'type' => $type->id(),
      'label' => 'Base microcontent entity',
    ]);

    $expected = [];
    $available_langcodes = array_keys($this->container->get('language_manager')
      ->getLanguages());
    $microcontent->set('langcode', reset($available_langcodes));

    // Create a few translations with a different label and keep track of the
    // label per language, for use in asserts later.
    foreach ($available_langcodes as $langcode) {
      $translation = $microcontent->hasTranslation($langcode) ? $microcontent->getTranslation($langcode) : $microcontent->addTranslation($langcode);
      $expected[$langcode] = "Translation for Base microcontent entity. Langcode: " . $langcode;
      $translation->set('label', "Translation for Base microcontent entity. Langcode: " . $langcode);
    }
    $microcontent->save();
    $microcontent_storage->resetCache();
    $microcontent = $microcontent_storage->load($microcontent->id());

    foreach ($available_langcodes as $langcode) {
      $translation = $microcontent->getTranslation($langcode);
      $this->assertEquals($translation->get('label')->value, $expected[$langcode]);
    }
  }

}
