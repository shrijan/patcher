<?php

namespace Drupal\linky\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Linky entities.
 */
class LinkyViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['linky']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Managed Link'),
      'help' => $this->t('The Managed Link ID.'),
    ];

    $data['linky']['user_id']['help'] = $this->t('The user authoring the link. If you need more fields than the uid add the Managed link: Author relationship');
    $data['linky']['user_id']['filter']['id'] = 'user_name';
    $data['linky']['user_id']['relationship']['title'] = $this->t('Author');
    $data['linky']['user_id']['relationship']['help'] = $this->t('Relate links to the user who created them.');
    $data['linky']['user_id']['relationship']['label'] = $this->t('author');

    return $data;
  }

}
