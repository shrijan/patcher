<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Service\VideoGalleryData;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'video_gallery',
)]
class VideoGallery extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  protected ?VideoGalleryData $videoGalleryData;

  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->videoGalleryData = \Drupal::service('dphi_components.video_gallery_data');
  }

  public function getFilterTags() {
    $filter_tags = [];
    if ($this->hasField('field_filter_tags') && !$this->get('field_filter_tags')->isEmpty()) {
      foreach ($this->get('field_filter_tags')->referencedEntities() as $term) {
        $filter_tags[] = $term->id();
      }
    }
    return $filter_tags;
  }

  public function getVideoGalleryData() {
    $filter_tags = $this->getFilterTags();
    $video_data = $this->videoGalleryData->getVideoData($filter_tags);
    $category_terms = $this->videoGalleryData->getCategoryTerms();

    $selected_filter_terms = [];
    if ($this->hasField('field_choose_available_filters') && !$this->get('field_choose_available_filters')->isEmpty()) {
      foreach ($this->get('field_choose_available_filters')->referencedEntities() as $term) {
        $selected_filter_terms[] = $term->id();
      }
    }

    $filtered_category_terms = $this->filterCategoryTerms($category_terms, $selected_filter_terms);

    return [
      'title' => $this->getSingleFieldValue('field_display_video_block_title') == '1' ? $this->getSingleFieldValue('field_video_block_title') : null,
      'video_data' => $video_data,
      'category_terms' => $filtered_category_terms,
      'display_layout' => $this->getSingleFieldValue('field_display_layout'),
      'show_filters' => $this->getSingleFieldValue('field_show_filters'),
      'theme' => $this->getSingleFieldValue('field_theme'),
    ];
  }

  protected function filterCategoryTerms(array $category_terms, array $selected_filter_terms) {
    $filtered_terms = [];

    foreach ($category_terms as $parent_term) {
      $filtered_children = [];

      foreach ($parent_term['children'] as $child_term) {
        if (in_array($child_term['id'], $selected_filter_terms)) {
          $filtered_children[] = $child_term;
        }
      }

      if (!empty($filtered_children) || in_array($parent_term['id'], $selected_filter_terms)) {
        $filtered_terms[] = [
          'id' => $parent_term['id'],
          'label' => $parent_term['label'],
          'children' => $filtered_children,
        ];
      }
    }

    return $filtered_terms;
  }
}
