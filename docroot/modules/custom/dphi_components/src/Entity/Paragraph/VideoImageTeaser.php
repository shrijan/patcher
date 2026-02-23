<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Service\VideoGalleryData;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;


#[Bundle(
  entityType: 'paragraph',
  bundle: 'video_image_teaser',
)]
class VideoImageTeaser extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  protected ?VideoGalleryData $videoGalleryData;

  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->videoGalleryData = \Drupal::service('dphi_components.video_gallery_data');
  }

  public function getComponent(): array {
    $link = $this->get('field_link')->first();
    $video_transcript = $this->get('field_video_transcript')->first();

    $component = [
      'description' => $this->getContentFieldValue('field_description'),
      'description_height_short' => $this->getSingleFieldValue('field_description_height_short') == '1',
      'keep_description_on_left' => $this->getSingleFieldValue('field_keep_description_on_left') == '1',
      'link_text' => $this->getSingleFieldValue('field_link_text'),
      'link_type' => $this->getSingleFieldValue('field_link_type'),
      'link_url' => $link ? $link->getUrl() : null,
      'title' => $this->getSingleFieldValue('field_title'),
      'background_color_dark' => $this->getSingleFieldValue('field_use_background_colour') == '1',
      'video_transcript' => $video_transcript ? $video_transcript->view() : null,
      'camera_icon' => $this->getBooleanValue('field_show_camera_icon'),
    ];
    $url = $this->getSingleFieldValue('field_youtube_video_link_url_');
    if ($url) {
      $data = $this->videoGalleryData->parseVideoUrl($url);
      if (!empty($data['embed'])) {
        $component['video_url'] = $data['embed'];
        $component['player_title'] = $data['player_title'];
      }
    }
    foreach (['background_image', 'image'] as $k) {
      $image = $this->get('field_media_'.$k)->entity;
      if ($image) {
        $component[$k] = \Drupal::entityTypeManager()->getViewBuilder('media')->view($image);
      }
    }
    return $component;
  }
}
