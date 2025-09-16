<?php

namespace Drupal\dphi_components\Service;

use Drupal\media\Entity\Media;

class VideoGalleryData {

  public function parseVideoUrl($url) {
    $data = [];
    if (!str_starts_with($url, 'https://vimeo.com/')) {
      $parsed_url = parse_url($url, PHP_URL_QUERY);
      $args = [];
      if ($parsed_url) {
        parse_str($parsed_url, $query_params);
        foreach ($query_params as $k=>$v) {
          if ($k == 'v') {
            $data['youtube_id'] = $v;
          } else {
            $args[$k] = $v;
          }
        }
      }
      if (empty($data['youtube_id']) && str_starts_with($url, 'https://youtu.be')) {
        $data['youtube_id'] = basename(parse_url($url, PHP_URL_PATH));
      }
      if (!empty($data['youtube_id'])) {
        $args['enablejsapi'] = '1';
        $data['embed'] = 'https://www.youtube.com/embed/'.$data['youtube_id'].'?'.http_build_query($args);
      }
    }
    return $data;
  }

  public function getVideoData($filter_tags = []) {
    $query = \Drupal::entityQuery('media')
      ->condition('bundle', 'remote_video')
      ->accessCheck(TRUE);

    // Apply filter tags if provided
    if (!empty($filter_tags)) {
      $query->condition('field_tags.entity.tid', $filter_tags, 'IN');
    }

    $ids = $query->execute();
    $video_media = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($ids);

    $video_data = [];
    foreach ($video_media as $media) {
      $video_data[] = $this->getMediaValues($media);
    }
    return $video_data;
  }

  public function getCategoryTerms(): array {
    $terms = [];
    $vocabulary = 'category';

    $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary, 0, 1);

    foreach ($parents as $parent) {
      $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary, $parent->tid, 1);

      $childTerms = [];
      foreach ($children as $child) {
        $childTerms[] = [
          'id' => $child->tid,
          'label' => $child->name,
        ];
      }

      $terms[] = [
        'id' => $parent->tid,
        'label' => $parent->name,
        'children' => $childTerms,
      ];
    }

    return $terms;
  }

  public function getMediaValues(Media $media): array
  {
    $video_data = [];
    if ($media->hasField('field_media_oembed_video') && !empty($media->get('field_media_oembed_video')->getString())) {
      $url = $media->get('field_media_oembed_video')->getString();
      $video_data = [
        'url' => $url,
        ...$this->parseVideoUrl($url)
      ];
    }

    $caption = '';
    if ($media->hasField('field_caption') && !empty($media->get('field_caption')->getString())) {
      $caption = $media->get('field_caption')->getString();
    }

    $transcript = '';
    if ($media->hasField('field_transcript_link')) {
      $transcript_link = $media->get('field_transcript_link');
      if (!$transcript_link->isEmpty()) {
        $transcript = $transcript_link[0]->getUrl()->toString();
      }
    }

    $title = '';
    if ($media->hasField('field_title') && !empty($media->get('field_title')->getString())) {
      $title = $media->get('field_title')->getString();
    }

    $tags = [];
    if ($media->hasField('field_tags') && !empty($media->get('field_tags')->referencedEntities())) {
      foreach ($media->get('field_tags')->referencedEntities() as $term) {
        $tags[] = $term->id();
      }
    }

    $created = $media->getCreatedTime();

    return [
        'id' => $media->id(),
        'title' => $title,
        'video_data' => $video_data,
        'caption' => $caption,
        'transcript' => $transcript,
        'tags' => $tags,
        'created' => $created,
    ];
  }
}
