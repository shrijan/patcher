<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'content_listing',
)]
class ContentListing extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $displayFormat = $this->getSingleFieldValue('field_display_format');

    $content_listing = [
      'title' => $this->getSingleFieldValue('field_heading'),
      'description' => $this->getContentFieldValue('field_description'),
      'display_format' => $displayFormat,
      'cards_per_row' => $this->getSingleFieldValue('field_cards_per_row'),
      'number_of_items' => $this->getSingleFieldValue('field_number_of_items'),
      'tags' => $this->get('field_tags')->getValue(),
      'sort_by' => $this->getSingleFieldValue('field_sort_order'),
    ];

    if (!$this->get('field_view_all')->isEmpty()) {
      $link = $this->get('field_view_all')->first();
      $view_all_url = $link->getUrl()->toString();
      $view_all_title = $link->getTitle();

      $content_listing['view_all'] = [
        'url' => $view_all_url,
        'title' => $view_all_title,
      ];
    }
    else {
      $content_listing['view_all'] = '';
    }

    // Fetch selected types and tags from the paragraph.
    $selected_types = array_column($this->get('field_category_type')
      ->getValue(), 'value') ?? [];
    $selected_tags = array_column($this->get('field_tags')
      ->getValue(), 'target_id') ?? [];

    // Fetch nodes using the NodeQueryService.
    $nodeQueryService = \Drupal::service('dphi_components.content_listing.node_query_service');

    $limit = !empty($content_listing['number_of_items']) && is_numeric($content_listing['number_of_items'])
      ? (int) $content_listing['number_of_items']
      : NULL;

    $nodeIds = $nodeQueryService->fetchNodeIds($selected_types, $selected_tags, $content_listing['sort_by'], $limit);

    $nodeStorage = $this->entityTypeManager()->getStorage('node');
    $nodeViewBuilder = $this->entityTypeManager()->getViewBuilder('node');

    // Load nodes.
    $nodes = $nodeStorage->loadMultiple($nodeIds);

    // Initialize an array to store preprocessed node data.
    $content_listing['nodes'] = [];

    // Prepare node data for rendering.
    foreach ($nodes as $node) {
      $content_listing['nodes'][] = $nodeViewBuilder->view($node, $displayFormat === 'cards' ? 'card' : 'list_item');
    }
    return $content_listing;
  }

}
