<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'documents',
)]
class Documents extends Paragraph {

  use FieldValueTrait;

  public function getComponent(): array {
    $file_url_generator = \Drupal::service('file_url_generator');
    $document['date'] = $this->get('field_date')->getString();
    $media = $this->get('field_file')->referencedEntities();
    if ($media) {
      $media = reset($media);
      $file = $media->get('field_media_document')->referencedEntities();
      $file = reset($file);
      $file_uri = $file->getFileUri();
      $document['file_url'] = $file_url_generator->generate($file_uri);
      $size = $file->getSize();
      $base = log($size, 1024);
      $suffixes = ['', 'KB', 'MB', 'GB', 'TB'];
      $file_uri = $file->getFileUri();
      $file_size = round(pow(1024, $base - floor($base)), 2) . ' ' . $suffixes[floor($base)];
      if (is_numeric($file_size)) {
        $file_size = $file_size/1000 .' KB';
      }
      $document['size'] = $file_size;
      $document['extension'] = pathinfo($file_uri, PATHINFO_EXTENSION);
      $document['name'] = pathinfo($file_uri, PATHINFO_FILENAME);
      $document['description'] = $media->get('field_media_document')[0]->description;
      if (!$document['description']) {
        $document['description'] = $document['name'];
      }
    }

    return $document;
  }

}
