<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'featured_publication',
  label: new TranslatableMarkup('Featured Publication'),
)]
class FeaturedPublication extends Paragraph {

  use PaddingControlTrait;
  use FieldValueTrait;

  public function getComponent(): array {
    if (!$this->get('field_fp_publication')->isEmpty()) {
      $referenced_publication_entities = $this->get('field_fp_publication')->referencedEntities();
      $publication_node = reset($referenced_publication_entities);

      if ($publication_node && $publication_node->bundle() == 'publications') {
        // Node ID
        $nid = $publication_node->id();

        // Published Date
        if (!$publication_node->get('field_publish_event_date')->isEmpty()) {
          $timestamp = strtotime($publication_node->get('field_publish_event_date')
            ->getString());
          $published_date = \Drupal::service('date.formatter')
            ->format($timestamp, 'custom', 'j F Y');
        }

        // Get the file size and format it
        if (!$publication_node->get('field_publication_file')->isEmpty()) {
          $file_url_generator = \Drupal::service('file_url_generator');
          $referenced_file_entities = $publication_node->get('field_publication_file')
            ->referencedEntities();
          $file = reset($referenced_file_entities);
          $size = $file->getSize();
          $base = log($size, 1024);
          $suffixes = ['', 'KB', 'MB', 'GB', 'TB'];
          $file_uri = $file->getFileUri();
          $file_info = pathinfo($file_uri);
          $file_size = $file_info['extension'] . ' ' . round(pow(1024, $base - floor($base)), 2) . ' ' . $suffixes[floor($base)];
          $file_url = $file_url_generator->generate($file_uri);
          $image_url = $publication_node->get('field_preview_image_url')
            ->getString();
        }

        // Thumbnail Image URL
        if (!$publication_node->get('field_media_image')->isEmpty()) {
          $media = $publication_node->get('field_media_image')->entity;
          if ($media->field_media_image->entity) {
            $image_url = $media->field_media_image->entity->createFileUrl();
          }
        }
        return [
          'heading' => $this->getSingleFieldValue('field_fp_heading'),
          'description' => $this->getContentFieldValue('field_fp_description'),
          'show_thumbnail' => $this->getBooleanValue('field_show_thumbnail'),
          'nid' => $nid,
          'published_date' => $published_date ?? '',
          'file_size' => $file_size ?? '',
          'file_url' => $file_url ?? '',
          'image_url' => $image_url ?? '',
        ];
      }
    }
    return [];
  }
}
