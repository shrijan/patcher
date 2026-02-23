<?php

namespace Drupal\dphi_components\Entity\Node;

use Drupal\bca\Attribute\Bundle;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

#[Bundle(
  entityType: 'node',
  bundle: 'publications',
)]
class Publication extends Node {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getSortDate(): string {
    $publication_date = $this->get('field_publish_event_date')->getString() ?? NULL;
    return $publication_date ? \Drupal::service('date.formatter')
      ->format(strtotime($publication_date), 'custom', 'Y-m-d') : '';
  }

  public function getComponent(): array {
    $file_name = $file_size = $file_url = $file_extension = $num_of_pages = '';
    $file_url_generator = \Drupal::service('file_url_generator');
    $file = $this->get('field_publication_file')->entity;
    if (is_object($file)) {
      $size = $file->getSize();
      $base = log($size, 1024);
      $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
      $precision = 2;
      $file_size = round(pow(1024, $base - floor($base)), $precision).' '.$suffixes[floor($base)];
      $file_url = $file_url_generator->generate($file->getFileUri());
      $file_name = \Drupal::service('file_system')->basename($file->getFileUri());
      $ext_array = explode('.', $file_name);
      $file_extension = array_pop($ext_array);
      $fileUri = $file->getFileUri();
      $pdf = NULL;
      $num_of_pages = NULL;
      if (file_exists($fileUri)) {
        $pdf = file_get_contents($fileUri);
        $num_of_pages = preg_match_all("/\/Page\W/", $pdf, $dummy);
      }
    }
    return [
      'content' => $this->getContentFieldValue('body'),
      'summary' => $this->get('body')->summary,
      'file_name' => $file_name,
      'file_size' => $file_size,
      'file_url' => $file_url,
      'file_extension' => $file_extension,
      'num_of_pages' => $num_of_pages,
      'types' => $this->getPublicationTypes(),
      'tags' => $this->getPublicationTags(),
      'date' => $this->getSingleFieldValue('field_publish_event_date'),
      'image_url' => $this->getSingleFieldValue('field_preview_image_url'),
    ];
  }

  protected function getPublicationTypes(): string {
    $field_publication_type = $this->get('field_publication_type')->getValue();
    $types = [];
    if (!empty($field_publication_type)) {
      foreach ($field_publication_type as $type) {
        $type_term = Term::load($type['target_id']);
        if ($type_term) {
          $types[] = $type_term->getName();
        }
      }
    }
    return implode(', ', array_splice($types, 0, 2));
  }

  protected function getPublicationTags(): array {
    $field_tags = $this->get('field_area')->getValue();
    $tags = [];
    if (!empty($field_tags)) {
      foreach ($field_tags as $tag) {
        $tag_term = Term::load($tag['target_id']);
        if ($tag_term) {
          $tags[] = $tag_term->getName();
        }
      }
    }
    return array_splice($tags, 0, 2);
  }

  /**
   * Set PDF preview image when no thumbnail uploaded for dynamic search
   */
  public function preSave(EntityStorageInterface $storage) {
    if ($this->bundle() == 'publications') {
      $media_image = $this->get('field_media_image')->getValue();
      if(!$media_image) {
        $file = File::load($this->get('field_publication_file')->target_id);
        if ($file) {
          $file_name = \Drupal::service('file_system')
            ->basename($file->getFileUri());
          $filename_array = explode('.', $file_name);
          $file_extension = array_pop($filename_array);
          if ($file_extension == 'pdf') {
            $uri = \Drupal::service('pdfpreview.generator')->getPDFPreview($file);
            if ($uri) {
              $image_url = \Drupal::service('file_url_generator')->generate($uri);
              $this->set('field_preview_image_url', $image_url->toString());
            }
          }
          else {
            $extension_path_resolver = \Drupal::service('extension.path.resolver');
            $active_theme_path = $extension_path_resolver->getPath('theme', 'dphi_base_theme');
            $default_image = '/' . $active_theme_path . '/images/publications/';
            switch ($file_extension) {
              case 'xlsx':
              case 'xls':
                $default_image .= 'default-xlsx.png';
                break;
              case 'doc':
              case 'docx':
                $default_image .= 'default-docx.png';
                break;
              case 'csv':
                $default_image .= 'default-csv.png';
                break;
            }
            if ($default_image) {
              $this->set('field_preview_image_url', $default_image);
            }
          }
        }
      }
    }
  }
}
