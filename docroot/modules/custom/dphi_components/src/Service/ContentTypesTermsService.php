<?php

namespace Drupal\dphi_components\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service class for fetching content types and terms.
 */
class ContentTypesTermsService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Field\FieldConfigInterface
   */
  protected $fieldManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ContentTypesTermsService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $fieldManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldManager = $fieldManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Fetches content types and terms with exclusions.
   *
   * @return array
   *   An associative array of content types and terms, minus exclusions.
   */
  public function getContentTypesAndTerms($applyExclusions = false) {
    $options = [];

    // Fetch content types.
    $contentTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($contentTypes as $contentType) {
        $key = $contentType->id();
        $options[$key] = $contentType->label();
    }

    // Fetch values from 'field_content_category' on the 'page' content type.
    $fieldDefinitions = $this->fieldManager->getFieldDefinitions('node', 'page');
    if (isset($fieldDefinitions['field_content_category'])) {
        $fieldStorageDefinition = $fieldDefinitions['field_content_category']->getFieldStorageDefinition();
        $allowedValues = options_allowed_values($fieldStorageDefinition);
        foreach ($allowedValues as $key => $value) {
            $options[$key] = $value;
        }
    }

    // Apply exclusions if required
    if ($applyExclusions) {
      $excludedItems = $this->getExcludedItems();
      foreach ($excludedItems as $excludedItem) {
          unset($options[$excludedItem]);
      }
    }

    return $options;
  }

  /**
   * Generates cache tags for content types and terms.
   *
   * @return array
   *   An array of cache tags.
   */
  public function getCacheTags() {
    $cacheTags = [];

    // Fetch all content types and taxonomy terms.
    $allItems = $this->getContentTypesAndTerms();

    // Iterate through all items and add their corresponding cache tags.
    foreach ($allItems as $key => $label) {
      if (strpos($key, 'taxonomy_term_') === 0) {
        // This is a taxonomy term, add a cache tag for it.
        $termId = str_replace('taxonomy_term_', '', $key);
        $cacheTags[] = 'taxonomy_term:' . $termId;
      } else {
        // This is a content type, add a cache tag for it.
        $cacheTags[] = 'node_type:' . $key;
      }
    }

    // Include additional cache tags as necessary, such as for the tag vocabulary.
    $cacheTags[] = 'vocabulary:tags';

    return $cacheTags;
  }


  /**

  Fetches the excluded content types and terms from configuration.
  @return array
  An array of excluded content types and terms.
  */
  public function getExcludedItems() {
    $config = $this->configFactory->get('dphi_components.content_listing.settings');
    $excludedItems = $config->get('excluded_values') ?: [];
    return $excludedItems;
  }
}
