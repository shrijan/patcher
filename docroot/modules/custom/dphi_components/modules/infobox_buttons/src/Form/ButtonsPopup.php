<?php

namespace Drupal\infobox_buttons\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates an icon dialog form for use in CKEditor.
 *
 * @package Drupal\material_icons\Form
 */
class ButtonsPopup extends FormBase {

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
    return 'info_buttons_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory->get('info_buttons_dialog.settings');
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';

   $options = ['nsw-button--dark' => 'Dark', 
               'nsw-button--danger' => 'Danger',
               'nsw-button--dark-outline' => 'Dark outline'];
    $arrow_opt = [' ' => '- None -', 
               'button-arrow-side-left' => 'Left side',
               'button-arrow-side-right' => 'Right side'];
    $form['buttons']['button_type'] = [
      '#title' => $this->t('Button type'),
      '#type' => 'select',
      '#options' => $options,
    ];
    
    $form['buttons']['arrow_type'] = [
      '#title' => $this->t('Add arrow2 to button'),
      '#type' => 'select',
      '#options' => $arrow_opt,
    ];

    $form['buttons']['button_text'] = [
      '#title' => $this->t('Button text'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];
    
    $form['buttons']['button_link'] = [
      '#title' => $this->t('Button link'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Insert Button'),
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

    $values = [
      'settings' => [
        'button_type' => $form_state->getValue('button_type'),
        'arrow_type' => $form_state->getValue('arrow_type'),
        'button_text' => $form_state->getValue('button_text'),
        'button_link' => $form_state->getValue('button_link'),
      ],
    ];

    $response->addCommand(new EditorDialogSave($values));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
