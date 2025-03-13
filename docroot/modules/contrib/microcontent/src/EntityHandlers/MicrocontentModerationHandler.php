<?php

namespace Drupal\microcontent\EntityHandlers;

use Drupal\content_moderation\Entity\Handler\ModerationHandler;
use Drupal\Core\Form\FormStateInterface;

/**
 * Customizations for micro-content entities.
 *
 * @phpstan-ignore-next-line Extending internal handler by design.
 */
class MicrocontentModerationHandler extends ModerationHandler {

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form['revision']['#disabled'] = TRUE;
    $form['revision']['#default_value'] = TRUE;
    $form['revision']['#description'] = $this->t('Revisions are required.');
  }

}
