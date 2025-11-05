<?php

namespace Drupal\microcontent\EntityHandlers;

use Drupal\views\EntityViewsData;

/**
 * Defines a class for microcontent views data.
 */
class MicrocontentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['microcontent_field_data']['uid']['filter']['id'] = 'user_name';
    $data['microcontent_field_revision']['uid']['filter']['id'] = 'user_name';
    $data['microcontent_field_revision']['id']['relationship']['id'] = 'standard';
    $data['microcontent_field_revision']['id']['relationship']['base'] = 'microcontent_field_data';
    $data['microcontent_field_revision']['id']['relationship']['base field'] = 'id';
    $data['microcontent_field_revision']['id']['relationship']['title'] = $this->t('Content');
    $data['microcontent_field_revision']['id']['relationship']['label'] = $this->t('Get the actual content from a content revision.');
    $data['microcontent_field_revision']['id']['relationship']['extra'][] = [
      'field' => 'langcode',
      'left_field' => 'langcode',
    ];
    return $data;
  }

}
