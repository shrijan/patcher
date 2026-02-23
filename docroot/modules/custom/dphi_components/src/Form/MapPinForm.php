<?php

namespace Drupal\dphi_components\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the map pin entity edit forms.
 */
class MapPinForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $arguments = ['%label' => $entity->label()];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New map pin %label has been created.', $arguments));
        $this->logger('dphi_components')->notice('Created new map pin %label', $arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The map pin %label has been updated.', $arguments));
        $this->logger('dphi_components')->notice('Updated map pin %label.', $arguments);
        break;
    }

    $form_state->setRedirect('entity.map_pin.collection');

    return $result;
  }
}
