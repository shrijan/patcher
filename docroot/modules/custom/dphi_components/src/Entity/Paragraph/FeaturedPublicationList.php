<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'featured_publication_list',
)]
class FeaturedPublicationList extends Paragraph {

  use PaddingControlTrait;
  use FieldValueTrait;

  public function getComponents(): array {
    $data = [
      'title' => $this->getSingleFieldValue('field_title'),
      'featured_publication_data' => []
    ];
    foreach ($this->get('field_featured_publications')->referencedEntities() as $featured_publication) {
      if ($featured_publication->get('field_fp_publication')->isEmpty()) {
        continue;
      }
      $referenced_publication_entities = $featured_publication->get('field_fp_publication')->referencedEntities();
      $publication_node = reset($referenced_publication_entities);
      if (!$publication_node || $publication_node->bundle() != 'publications') {
        continue;
      }

      $publication_data = [
        'heading' => $featured_publication->get('field_fp_heading')->getString(),
        'description' => $featured_publication->get('field_fp_description')->view([
          'label' => 'hidden',
          'type' => 'text_default',
        ]),
        'show_thumbnail' => $featured_publication->field_show_thumbnail->value,
        'nid' => $publication_node->id(),
      ];

      // Published Date
      if (!$publication_node->get('field_publish_event_date')->isEmpty()) {
        $timestamp = strtotime($publication_node->get('field_publish_event_date')->getString());
        $publication_data['published_date'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'j F Y');
      }

      // Get the file size and format it
      $image = null;
      if (!$publication_node->get('field_publication_file')->isEmpty()) {
        $file_url_generator = \Drupal::service('file_url_generator');
        $referenced_file_entities = $publication_node->get('field_publication_file')->referencedEntities();
        $file = reset($referenced_file_entities);
        $size = $file->getSize();
        $base = log($size, 1024);
        $suffixes = ['', 'KB', 'MB', 'GB', 'TB'];
        $file_uri = $file->getFileUri();
        $file_info = pathinfo($file_uri);
        $file_size = round(pow(1024, $base - floor($base)), 2) . ' ' . $suffixes[floor($base)];
        if (is_numeric($file_size)) {
          $file_size = $file_size/1000 .' KB';
        }
        $publication_data['file_size'] = $file_info['extension'] . ' ' . $file_size;
        $publication_data['file_url'] = $file_url_generator->generate($file_uri);

        $url = $publication_node->get('field_preview_image_url')->getString();
        if ($url) {
          $image = compact('url');
        }
      }
      $publication_data['image'] = $image ?: $publication_node->getImageWithFallback();

      $data['featured_publication_data'][] = $publication_data;
    }
    return $data;
  }
}
