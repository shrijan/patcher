<?php

namespace Drupal\dphi_components\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

class FilePreSaveHook {

  public static function preSave(EntityInterface $file): void {
    if ($file->isNew()) {
      return;
    }

    $originalFile = File::load($file->id());
    $originalFileName = $originalFile->getFilename();
    $newFileName = $file->getFilename();
    if ($newFileName === $originalFileName) {
      return;
    }

    $mediaReferences = self::getMediaReferences($file);
    if (empty($mediaReferences)) {
      return;
    }

    $mediaItems = Media::loadMultiple($mediaReferences);
    foreach ($mediaItems as $media) {
      $mediaLabel = $media->label();

      // Only rename media if it already had the same label as the file name.
      if ($mediaLabel !== $originalFileName) {
        continue;
      }

      $media->set('name', $newFileName)->save();
    }
  }

  protected static function getMediaReferences(EntityInterface $file): array {
    $mediaStorage = \Drupal::entityTypeManager()->getStorage('media');
    $query = $mediaStorage->getQuery('OR')->accessCheck(FALSE);

    $documentCondition = $query->andConditionGroup();
    $documentCondition->condition('bundle', 'document')
      ->condition('field_media_document', $file->id());
    $query->condition($documentCondition);

    $imageCondition = $query->andConditionGroup();
    $imageCondition->condition('bundle', 'image')
      ->condition('field_media_image', $file->id());
    $query->condition($imageCondition);

    $audioCondition = $query->andConditionGroup();
    $audioCondition->condition('bundle', 'audio')
      ->condition('field_media_audio_file', $file->id());
    $query->condition($audioCondition);

    $videoCondition = $query->andConditionGroup();
    $videoCondition->condition('bundle', 'video')
      ->condition('field_media_video_file', $file->id());
    $query->condition($videoCondition);

    return $query->execute();
  }

}
