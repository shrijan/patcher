<?php

namespace Drupal\dphi_components\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure admin settings for this site.
 */
class AdminSettingsForm extends ConfigFormBase {

  public function __construct(ConfigFactoryInterface $config_factory, protected ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dphi_components_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dphi_components.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config =  $this->config('dphi_components.settings');

    $form['menu_widget_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use the replacement menu widget (BETA)'),
      '#default_value' => $config->get('menu_widget_enabled') ?? FALSE,
    ];

    // Define form elements for API keys
    $form['api_keys'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Google Cloud Map API Keys'),
      '#default_value' => \Drupal::keyValue('dphi_components')->get('api_keys'),
      '#description' => $this->t('Enter each API key with a meaningful name followed by "|" and then the key itself, one per line. E.g., "Primary Key |CIzaSnD...".'),
    ];

    // Add Google Cloud Map ID field
    $form['google_cloud_map_id'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Google Cloud Map IDs'),
      '#default_value' => \Drupal::keyValue('dphi_components')->get('google_cloud_map_id'),
      '#description' => $this->t('Enter each Map ID with a meaningful name followed by "|" and then the ID itself, one per line. E.g., "Primary ID |efa63...".'),
    ];

    $form['fallback_image'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fallback image'),
    ];

    $form['fallback_image']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image path'),
      '#description' => $this->t('Override the default path to the fallback image if desired.'),
      '#default_value' => $config->get('fallback_image_path') ?? FALSE,
      '#placeholder' => '/modules/custom/dphi_components/themes/dphi_base_theme/images/fallback-image.jpg',
      '#size' => 120,
    ];

    $form['fallback_image']['alt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alt text'),
      '#description' => $this->t('Override the alternative text for the fallback image if desired.'),
      '#default_value' => $config->get('fallback_image_alt_text') ?? FALSE,
      '#placeholder' => $this->t('NSW Government logo'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dphi_components.settings')
      ->set('menu_widget_enabled', $form_state->getValue('menu_widget_enabled'))
      ->set('fallback_image_path', trim($form_state->getValue('path')))
      ->set('fallback_image_alt_text', trim($form_state->getValue('alt_text')))
      ->save();
    \Drupal::keyValue('dphi_components')->set('api_keys', $form_state->getValue('api_keys'));
    \Drupal::keyValue('dphi_components')->set('google_cloud_map_id', $form_state->getValue('google_cloud_map_id'));
    $this->moduleHandler->resetImplementations();
    parent::submitForm($form, $form_state);
  }

}
