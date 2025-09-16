<?php

namespace Drupal\dphi_components\StaticHelper;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\field\Entity\FieldStorageConfig;

class AllowedValuesCallbacks {

  public static function apiKeysAllowedValues(FieldStorageConfig $definition, ContentEntityInterface $entity = NULL, &$cacheable): array {
    $cacheable = TRUE;

    // Fetch API keys from the configuration.
    $apiKeysString = \Drupal::keyValue('dphi_components')->get('api_keys');
    // Split the string on both \r\n and \n
    $lines = $apiKeysString ? preg_split("/\r\n|\n/", $apiKeysString) : [];
    $allowedValues = [];

    foreach ($lines as $line) {
      if (strpos($line, '|') !== FALSE) {
        [$key, $value] = explode('|', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!empty ($key) && !empty ($value)) {
          $allowedValues[$value] = $key; // Use the value as the key and the key as the option label
        }
      }
    }

    return $allowedValues;
  }

  public static function contentListingAllowedValues(FieldStorageConfig $definition, ContentEntityInterface $entity = NULL, &$cacheable): array {
    /** @var \Drupal\content_listing\ContentTypesTermsService $contentTypesTermsService */
    $contentTypesTermsService = \Drupal::service('dphi_components.content_listing.content_types_terms_service');
    return $contentTypesTermsService->getContentTypesAndTerms(TRUE);
  }

  public static function mapIdsAllowedValues(FieldStorageConfig $definition, ContentEntityInterface $entity = NULL, &$cacheable): array {
    $cacheable = TRUE;

    // Fetch Map IDs from the configuration.
    $mapIDsString = \Drupal::keyValue('dphi_components')->get('google_cloud_map_id');
    // Split the string on both \r\n and \n
    $lines = $mapIDsString ? preg_split("/\r\n|\n/", $mapIDsString) : [];
    $allowedValues = [];

    foreach ($lines as $line) {
      if (strpos($line, '|') !== FALSE) {
        [$key, $value] = explode('|', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!empty ($key) && !empty ($value)) {
          $allowedValues[$value] = $key; // Use the value as the key and the key as the option label
        }
      }
    }

    return $allowedValues;
  }

}
