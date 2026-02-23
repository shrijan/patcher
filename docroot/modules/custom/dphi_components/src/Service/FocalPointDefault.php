<?php

namespace Drupal\dphi_components\Service;

class FocalPointDefault {

  public static function update10201(&$sandbox): void {
    // Load the entity type manager service.
    $entity_type_manager = \Drupal::entityTypeManager();
    $media_storage = $entity_type_manager->getStorage('media');
    $crop_type = \Drupal::config('focal_point.settings')->get('crop_type');

    // Initialize sandbox variables for first run.
    if (!isset($sandbox['progress'])) {
      $sandbox['progress'] = 0;
      $sandbox['total'] = 0;

      // Get all media entity IDs of the 'image' bundle.
      $sandbox['media_ids'] = $media_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'image')
        ->execute();

      // Set the total for sandbox progress.
      $sandbox['total'] = count($sandbox['media_ids']);
    }

    // Load media entities in batches of 20.
    $media_ids = array_slice($sandbox['media_ids'], $sandbox['progress'], 20);
    $imageFactory = \Drupal::service('image.factory');

    foreach ($media_storage->loadMultiple($media_ids) as $media) {
      // Get the associated image file entity from the media field.
      if (!$media->hasField('field_media_image')) {
        continue;
      }

      if (!$files = $media->get('field_media_image')->referencedEntities()) {
        continue;
      }

      $file = reset($files);
      if (!($file instanceof \Drupal\file\FileInterface)) {
        continue;
      }

      $image_uri = $file->getFileUri();
      $image = $imageFactory->get($image_uri);
      if (!$image->isValid()) {
        continue;
      }

      // Get the dimensions of the image.
      $width = $image->getWidth();
      $height = $image->getHeight();

      // Set default focal point to the center of the image.
      $default_focal_x = $width / 2;
      $default_focal_y = $height / 2;

      // Check if this media entity has an associated crop.
      $crop_storage = $entity_type_manager->getStorage('crop');
      $crop_entities = $crop_storage->loadByProperties([
        'uri' => $image_uri,
        'type' => $crop_type,
      ]);

      // If there is a crop entity already, skip.
      if (!empty($crop_entities)) {
        continue;
      }

      // Create a new crop entity if none exists.
      $crop = $crop_storage->create([
        'type' => $crop_type,
        'entity_id' => $file->id(),
        'entity_type' => 'file',
        'uri' => $image_uri,
        'x' => $default_focal_x,
        'y' => $default_focal_y,
        'width' => 0,
        'height' => 0,
      ]);

      $crop->save();
    }

    // Update sandbox progress.
    $sandbox['progress'] += count($media_ids);

    // If all media entities are processed, mark as finished.
    if ($sandbox['progress'] >= $sandbox['total']) {
      $sandbox['#finished'] = 1;
    }
    else {
      // Calculate the progress for display.
      $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
    }
  }

}
