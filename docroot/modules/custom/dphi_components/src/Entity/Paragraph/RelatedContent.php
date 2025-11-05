<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\views\Views;


#[Bundle(
  entityType: 'paragraph',
  bundle: 'related_content',
)]
class RelatedContent extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $view = Views::getView('related_content');

    $numberOfItems = $this->getSingleFieldValue('field_number_of_items_to_display');
    $view->setItemsPerPage($numberOfItems ? intval($numberOfItems) : 6);

    $args = [$this->getSingleFieldValue('field_content_category')];
    $tags = [];
    foreach ($this->get('field_tags') as $tag) {
      $tags[] = $tag->target_id;
    }
    if ($tags) {
      $args[] = implode(',', $tags);
    }
    $view->setArguments($args);

    $rendered_view = $view->render('block_1');
    if (!empty($rendered_view['#rows'][0]['#rows'])) {
      foreach ($rendered_view['#rows'][0]['#rows'] as &$row) {
        $image = $row['#node']->get('field_media_image');
        $row['image'] = $image->entity ? $image->view('media') : null;
      }
    }
    $component = [
      'title' => $this->getSingleFieldValue('field_title'),
      'view' => $rendered_view
    ];
    return $component;
  }
}
