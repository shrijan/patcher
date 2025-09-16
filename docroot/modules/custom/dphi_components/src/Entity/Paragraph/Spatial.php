<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\views\Views;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'spatial_component',
  label: new TranslatableMarkup('Interactive map'),
)]
class Spatial extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent() {
    $view = Views::getView('spatial_components');
    $view->setDisplay('block_list');
    $view->preExecute();
    $view->execute();
    return [
      'heading' => $this->getSingleFieldValue('field_title'),
      'heading_size' => $this->getSingleFieldValue('field_heading_style') ?: 'h1',
      'description' => $this->getContentFieldValue('field_description'),
      'map_view' => $view->render('block_list')
    ];
  }

}
