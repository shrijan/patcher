<?php

namespace Drupal\Tests\scanner\Functional;

/**
 * Tests the search and replace functionality.
 *
 * @group scanner
 */
class SearchReplaceTest extends ScannerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Make sure to complete the normal setup steps first.
    parent::setUp();

    // Create a test content types.
    $this->createContentTypeNode('Title test', 'Body test', 'scanner_test_node_type', 'Scanner test node type');

    // Log in as an admin who can modify the module's settings.
    $user = $this->createUser(['administer scanner settings']);
    $this->drupalLogin($user);

    // Enable the content type.
    $this->drupalGet('admin/config/content/scanner');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'enabled_content_types[node:scanner_test_node_type]' => 'node:scanner_test_node_type',
    ];
    $this->submitForm($edit, 'Save configuration');
    $edit = [
      'fields_of_selected_content_type[node:scanner_test_node_type:body]' => 'node:scanner_test_node_type:body',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);

    // Log in as a user that can use the replace system.
    $user = $this->createUser([
      // 'administer nodes',
      'perform search only',
      'perform search and replace',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Test the complete search & replace operation in one go.
   */
  public function testSearchReplace() {
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Body test');

    // Load the main scanner form.
    $this->drupalGet('admin/content/scanner');
    $session_assert = $this->assertSession();
    $session_assert->statusCodeEquals(200);

    // Verify the form has the expected fields.
    $session_assert->fieldExists('search');
    $session_assert->fieldExists('replace');
    $session_assert->fieldExists('preceded');
    $session_assert->fieldExists('followed');
    $session_assert->fieldExists('mode');
    $session_assert->fieldExists('wholeword');
    $session_assert->fieldExists('regex');
    $session_assert->fieldExists('published');
    $session_assert->fieldExists('language');

    // Test the search operation.
    $this->submitForm(['search' => 'Body test'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    // Make sure no errors were reported.
    $this->assertSession()->pageTextNotContains('An error has occurred.');
    $this->assertSession()->pageTextNotContains('Found 0 matches in 0 entities.');
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');
    $this->submitForm(['search' => 'scanner'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Found 1 matches in 1 entities.');
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');

    // Test the replacement operation.
    $this->submitForm(['search' => 'Body test', 'replace' => 'scanner'], 'Replace');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Confirm');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('1 entities processed.');

    // Verify that the string changed.
    $this->submitForm(['search' => 'Body test'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Found 0 matches in 0 entities.');
    $this->submitForm(['search' => 'scanner'], 'Search');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Found 1 matches in 1 entities.');

    // Verify that the node's text has changed.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Body test');
    $this->assertSession()->pageTextContains('scanner');
  }

}
