<?php

namespace Drupal\linky\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A class to provide a settings form for linky.
 */
class LinkySettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      $this->getConfigName(),
    ];
  }

  /**
   * A method to get the config name.
   *
   * @return string
   *   The config name.
   */
  private function getConfigName(): string {
    return 'linky.settings';
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'linky_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config($this->getConfigName());
    $form = parent::buildForm($form, $form_state);
    $form['additional_schemes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Additional schemes'),
      '#description' => $this->t("Select additional schemes to support in addition to HTTP and HTTPS."),
      '#options' => [
        'telephone' => $this->t('Telephone numbers @format', ['@format' => '(tel:)']),
        'email' => $this->t('Email addresses @format', ['@format' => '(mailto:)']),
      ],
      '#default_value' => [
        'telephone' => $config->get('additional_schemes.telephone') ? 'telephone' : 0,
        'email' => $config->get('additional_schemes.email') ? 'email' : 0,
      ],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->cleanValues();
    $config = $this->config($this->getConfigName());
    $config->set("additional_schemes.telephone", (bool) $form_state->getValue(['additional_schemes', 'telephone']));
    $config->set("additional_schemes.email", (bool) $form_state->getValue(['additional_schemes', 'email']));
    $config->save();
    // Ensure the base field info is rebuilt to re-apply the constraints.
    Cache::invalidateTags(['entity_field_info']);
  }

}
