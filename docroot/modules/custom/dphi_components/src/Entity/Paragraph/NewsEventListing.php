<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\ListsFieldTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\views\Views;


#[Bundle(
  entityType: 'paragraph',
  bundle: 'news_event_listing_block',
)]
class NewsEventListing extends Paragraph {

  use ListsFieldTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $view = Views::getView('news_and_events');

    $itemsPerPage = $this->getSingleFieldValue('field_number_of_items_to_display');
    $view->setItemsPerPage($itemsPerPage === '' ? 6 : intval($itemsPerPage));
    $this->addCacheableDependency($view);

    $link = $this->get('field_link')->first();
    return [
      'title' => $this->getSingleFieldValue('field_title'),
      'use_background_color' => $this->getSingleFieldValue('field_use_background_colour') == '1',
      'link_text' => $this->getSingleFieldValue('field_link_text'),
      'link_url' => $link ? $link->getUrl() : null,
      'list' => array_map(function ($row) {
        $node = $row['#node'];
        $this->addCacheableDependency($node);
        $short_description = $node->get('field_short_description');
        return [
          'content_category_value' => $node->get('field_content_category')->getString(),
          'image' => $node->getImageWithFallback(),
          'title' => $node->getTitle(),
          'description' => !$short_description->isEmpty() ? $short_description->view(['label' => 'hidden']) : null,
          'date' => $node->get('field_publish_event_date'),
          'link' => $node->toUrl(),
          'tags' => $node->get('field_tags')
        ];
      }, $view->render('block_1')['#rows'][0]['#rows'])
    ];
  }
}
