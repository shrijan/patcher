<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\Core\Form\FormState;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\views\Views;
use Drupal\views\Entity\View;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'hero_banner_search',
)]
class HeroBannerSearch extends Paragraph {

  use FieldValueTrait;

  public function getComponent(): array {
    $acquiaSearch = View::load('acquia_search');
    // If Acquia search is enabled, use that.
    if ($acquiaSearch->status()) {
      $view = Views::getView('acquia_search');
    }
    else {
      $view = Views::getView('contentt_search');
    }
    $view->setDisplay('banner');
    $view->initHandlers();
    $form_state = new FormState();
    $form_state->setFormState([
      'view' => $view,
      'display' => $view->display_handler->display,
      'exposed_form_plugin' => $view->display_handler->getPlugin('exposed_form'),
      'rerender' => TRUE,
    ])->setMethod('get')
      ->setAlwaysProcess(TRUE)
      ->disableRedirect();
    $form = \Drupal::formBuilder()
      ->buildForm('Drupal\views\Form\ViewsExposedForm', $form_state);
    $form['input-autocomplete']['#attributes']['placeholder'] = t('What would you like to find?');
    $form['sort_by']['#value'] = 'search_api_relevance';
    $prefilters = $this->getSingleFieldValue('field_enable_prefilters') == '1';
    if ($prefilters) {
      $form['field_category']['#prefix'] = '<div class="nsw-filters__list" tabindex="0">';
      $form['field_topics']['#suffix'] = '</div>';
      $form['actions']['#prefix'] = '<div class="filter-results"><div class="trigger">' . t('Filter results') . '</div>';
      $form['actions']['#prefix'] .= '<a href="" class="material-icons nsw-material-icons keyboard-arrow-right" aria-label="Filter results">keyboard_arrow_right</a>';
    }
    else {
      unset($form['field_category'], $form['field_location'], $form['field_topics']);
      $form['actions']['#prefix'] = '<div class="filter-results">';
    }
    $form['actions']['#suffix'] = '<span class="material-icons nsw-material-icons icon-search" focusable="true" aria-hidden="true">search</span>';
    $form['actions']['#suffix'] .= '</div>';

    $media = $this->get('field_image')->referencedEntities()[0] ?? NULL;
    if ($media) {
      $image = $media->get('field_media_image')->referencedEntities()[0];
      if ($image) {
        $image_info = $this->getFocalPointCoordinates($image);
      }
    }
    $links = [];
    foreach ($this->get('field_hero_search_cta_links') as $link) {
      $links[] = [
        'text' => $link->title,
        'url' => $link->getUrl(),
      ];
    }
    return [
      'camera_icon' => $this->getBooleanValue('field_show_camera_icon'),
      'title' => $this->getSingleFieldValue('field_title'),
      'description' => $this->getSingleFieldValue('field_description_caption'),
      'image' => $media ? \Drupal::service('file_url_generator')
        ->generate($media->field_media_image->entity->getFileUri())
        ->toString() : NULL,
      'image_credit' => $media ? $media->getSingleFieldValue('field_image_credit') : '',
      'image_description' => $media ? $media->getSingleFieldValue('field_image_description') : '',
      'focal_x' => $image_info['focal_x'] ?? NULL,
      'focal_y' => $image_info['focal_y'] ?? NULL,
      'links' => $links,
      'form' => $form,
      'prefilters' => $prefilters,
    ];
  }

}
