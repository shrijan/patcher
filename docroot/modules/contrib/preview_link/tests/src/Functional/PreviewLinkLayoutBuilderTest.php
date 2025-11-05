<?php

declare(strict_types=1);

namespace Drupal\Tests\preview_link\Functional;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestRevPub;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Integration test for the preview link and layout builder.
 *
 * @group preview_link
 */
class PreviewLinkLayoutBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'preview_link_test',
    'preview_link',
    'entity_test',
    'layout_builder',
  ];

  /**
   * Test there is no preview link redirection on layout builder pages.
   */
  public function testNoRedirectOnLayoutPage(): void {
    $user = $this->createUser([
      'generate preview links',
      'view test entity',
      'configure any layout',
    ]);
    $entity = EntityTestRevPub::create(['name' => 'test entity 1'])->setPublished();
    $entity->save();

    // Enable layout builder overrides.
    LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test_revpub',
      'bundle' => 'entity_test_revpub',
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    \Drupal::configFactory()
      ->getEditable('preview_link.settings')
      ->set('enabled_entity_types', [
        'entity_test_revpub' => ['entity_test_revpub'],
      ])
      ->save();
    $this->drupalLogin($user);
    $this->drupalGet($entity->toUrl('preview-link-generate'));
    $link = $this->cssSelect('.preview-link__link')[0]->getText();
    $this->drupalGet($link);

    $this->drupalGet(Url::fromRoute('layout_builder.overrides.entity_test_revpub.view', [
      'entity_test_revpub' => $entity->id(),
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals(sprintf('entity_test_revpub/manage/%s/layout', $entity->id()));
  }

}
