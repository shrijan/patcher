<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\ListsFieldTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;


#[Bundle(
  entityType: 'paragraph',
  bundle: 'stories_case_studies_listing_blo',
)]
class StoriesCaseStudiesListing extends Paragraph {

  use ListsFieldTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $link = $this->get('field_link')->first();
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'link_text' => $this->getSingleFieldValue('field_link_text'),
      'link_url' => $link ? $link->getUrl() : null,
      'list' => array_map(function ($node) {
        $this->addCacheableDependency($node);
        $image = $node->get('field_media_image');
        $short_description = $node->get('field_short_description');
        return [
          'content_category_value' => $node->get('field_content_category')->getString(),
          'image' => $image->entity ? $image->view('media') : null,
          'title' => $node->getTitle(),
          'description' => !$short_description->isEmpty() ? $short_description->view(['label' => 'hidden']) : null,
          'date' => $node->get('field_publish_event_date'),
          'link' => $node->toUrl(),
          'tags' => $node->get('field_tags')
        ];
      }, $this->get('field_target_content')->referencedEntities())
    ];
  }
}
