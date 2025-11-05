<?php

namespace Drupal\Tests\views_sort_null_field\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests for the basic functionality of Views Sort Null Field.
 *
 * @group views_sort_null_field
 */
class BasicTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field',
    'text',
    'user',
    'views_sort_null_field',
    'views_sort_null_field_test',
  ];

  /**
   * Views to import.
   *
   * @var array
   */
  public static $testViews = ['views_sort_null_field_test'];

  /**
   * {@inheritdoc}
   *
   * @param bool $import_test_views
   *   Should the views specified on the test class be imported. If you need
   *   to setup some additional stuff, like fields, you need to call false and
   *   then call createTestViews for your own.
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig([
      'node',
      'user',
      'field',
      'views_sort_null_field_test',
    ]);

    ViewTestData::createTestViews(get_class($this), ['views_sort_null_field_test']);
  }

  /**
   * Test that null first ordering works.
   */
  public function testNullOrdering() {
    $nodes = [
        [
          'type' => 'views_sort_null_field_content',
          'title' => 'node1',
          'field_text' => ['value' => 'body1'],
        ],
        [
          'type' => 'views_sort_null_field_content',
          'title' => 'node2',
        ],
        [
          'type' => 'views_sort_null_field_content',
          'title' => 'node3',
          'field_text' => ['value' => 'body2'],
        ],
    ];
    foreach ($nodes as $node_values) {
      $node = Node::create($node_values);
      $node->save();
    }
    $view = Views::getView('views_sort_null_field_test');
    $view->setDisplay();
    $view->preview('default');
    $this->assertIdenticalResultset(
      $view,
      [
        ['title' => 'node2'],
        ['title' => 'node1'],
        ['title' => 'node3'],
      ],
      ['title' => 'title']
    );
  }

}
