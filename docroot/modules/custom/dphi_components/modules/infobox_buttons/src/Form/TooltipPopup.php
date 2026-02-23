<?php

namespace Drupal\infobox_buttons\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates an icon dialog form for use in CKEditor.
 *
 * @package Drupal\material_icons\Form
 */
class TooltipPopup extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * IconDialog constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tooltip_buttons_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory->get('tooltip_buttons_dialog.settings');
    
    // Get query parameters
    $tooltipText = \Drupal::request()->query->get('tooltipText');
    $tooltipTheme = \Drupal::request()->query->get('tooltipTheme');
    $tooltipToggle = \Drupal::request()->query->get('tooltipToggle');
    $tooltipContent = \Drupal::request()->query->get('tooltipContent');
  
    
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    
    $options = ['dark' => 'Dark', 
               'light' => 'Light'];
    
    $form['tooltip_buttons']['tooltip_theme'] = [
      '#title' => $this->t('Theme'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $tooltipTheme,
    ];
    
    $form['tooltip_buttons']['tooltip_toggle'] = [
        '#title' => $this->t('Enable Toggletip'),
        '#type' => 'checkbox',
        '#description' => $this->t('Check this box to enable the Toggletip feature.'),
        '#default_value' => $tooltipToggle == 'true' ? TRUE : FALSE,
    ];

    
    $form['tooltip_buttons']['tooltip_button_text'] = [
      '#title' => $this->t('Tooltip description'),
      '#type' => 'textarea',
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['js-text-full', 'text-full'],
      ],
      '#default_value' => $tooltipContent,
    ];
    
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $timestamp = time();
    $key_hash = hash('crc32b', $timestamp);
    $id = 'tooptip-id-'.$key_hash;
    $values = [
      'settings' => [
        'tooltip_text' => $form_state->getValue('tooltip_button_text'),
        'tooltip_toggle' => $form_state->getValue('tooltip_toggle'),
        'theme' =>  $form_state->getValue('tooltip_theme'),
        'id' => $id,
      ],
    ];

    $response->addCommand(new EditorDialogSave($values));
    $response->addCommand(new CloseModalDialogCommand());


    return $response;
  }

}
