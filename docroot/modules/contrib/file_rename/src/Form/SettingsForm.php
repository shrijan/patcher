<?php

namespace Drupal\file_rename\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contains settings form for file_rename module.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * Config settings.
   *
   * @var string Config object name.
   */
  const SETTINGS = 'file_rename.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_rename_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['always_show_widget_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display "Rename" link on all file fields.'),
      '#description' => $this->t('Enables a possibility to rename files directly from upload field. Also can be enabled for a single field in form display.'),
      '#default_value' => $config->get('always_show_widget_link'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration & save.
    $this->config(static::SETTINGS)
      ->set('always_show_widget_link', $form_state->getValue('always_show_widget_link'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
