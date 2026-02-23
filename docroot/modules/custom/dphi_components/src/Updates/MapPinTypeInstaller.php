<?php

namespace Drupal\dphi_components\Updates;

use Drupal\Core\Field\BaseFieldDefinition;

class MapPinTypeInstaller {

  /**
   * Install the MapPinType bundle entity and create a default.
   */
  public static function installMapPinType() {

    $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    $entity_type = \Drupal::entityTypeManager()->getDefinition('map_pin_type');
    $definition_update_manager->installEntityType($entity_type);

    // First ensure the default bundle exists
    $bundle_storage = \Drupal::entityTypeManager()->getStorage('map_pin_type');
    $default_bundle = $bundle_storage->load('map_pin');

    if (!$default_bundle) {
      $default_bundle = $bundle_storage->create([
        'uuid' => '5aad0c04-9a00-45fd-8aa0-27277379d68c',
        'id' => 'map_pin',
        'label' => 'Gallery Map Pin',
        'description' => 'Pins for the Gallery Map component',
      ]);
      $default_bundle->save();
    }

    $entity_type = $definition_update_manager->getEntityType('map_pin');

    $bundle_field = BaseFieldDefinition::create('entity_reference')
      ->setLabel($entity_type->getBundleLabel())
      ->setSetting('target_type', 'map_pin_type')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $definition_update_manager->installFieldStorageDefinition(
      'bundle',
      $entity_type->id(),
      'dphi_components',
      $bundle_field
    );

    \Drupal::entityTypeManager()->clearCachedDefinitions();
    drupal_flush_all_caches();
  }

  /**
   * Set a default value for existing entities.
   */
  public static function updateMapPins(&$sandbox) {

    if (!isset($sandbox['progress'])) {
      $sandbox['progress'] = 0;
      $sandbox['current_id'] = 0;
      $sandbox['max'] = \Drupal::entityQuery('map_pin')
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }

 
    $entity_ids = \Drupal::entityQuery('map_pin')
      ->condition('id', $sandbox['current_id'], '>')
      ->accessCheck(FALSE)
      ->sort('id')
      ->range(0, 10)
      ->execute();


    if ($entity_ids) {
      $storage = \Drupal::entityTypeManager()->getStorage('map_pin');
      $entities = $storage->loadMultiple($entity_ids);

      foreach ($entities as $entity) {
        // Set a default bundle value
        $entity->set('bundle', 'map_pin');
        $entity->save();

        $sandbox['progress']++;
        $sandbox['current_id'] = $entity->id();
      }
    }
    
    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : min(($sandbox['progress'] / $sandbox['max']), 1);

    if ($sandbox['#finished'] >= 1) {
      return t('Installed bundle field and updated @count existing entities.', [
        '@count' => $sandbox['progress'],
      ]);
    }
  }

}

