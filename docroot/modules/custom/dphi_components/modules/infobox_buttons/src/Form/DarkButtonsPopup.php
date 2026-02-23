<?php

namespace Drupal\infobox_buttons\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
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
class DarkButtonsPopup extends FormBase {

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
    return 'dark_buttons_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory->get('dark_buttons_dialog.settings');
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';

   $form['dark_buttons']['dark_button_text'] = [
      '#title' => $this->t('Button text'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#suffix' => '<div class="error" id="button-text"></div>',
    ];

    $form['dark_buttons']['dark_button_link_type'] = [
      '#title' => $this->t('Link type'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        'content' => $this->t('Content'),
        'document' => $this->t('Document'),
        ],
      '#suffix' => '<div class="error" id="link-type"></div>',
    ];

    $form['dark_buttons']['dark_button_node_link'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Button link'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="dark_button_link_type"]' => ['value' => 'content'],
        ],
      ],
      '#suffix' => '<div class="error" id="button-link"></div>',
    ];


    $form['dark_buttons']['dark_button_document_link'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#title' => $this->t('Button link'),
      '#selection_settings' => array(
        'target_bundles' => array('document'),
      ),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="dark_button_link_type"]' => ['value' => 'document'],
        ],
      ],
      '#suffix' => '<div class="error" id="button-link-document"></div>',
    ];


   $options = ['before' => 'Before',
               'after' => 'After'];

    $tab_opt = ['_self' => 'Same tab',
                '_blank' => 'New tab'];

    $form['dark_buttons']['dark_button_type'] = [
      '#title' => $this->t('Icon type'),
      '#type' => 'select',
      '#options' => $options,
    ];

    $form['dark_buttons']['target'] = [
      '#title' => $this->t('Link target'),
      '#type' => 'select',
      '#options' => $tab_opt,
    ];

     $form['notice'] = [
      '#markup' => $this->t('Select the icon and style to be displayed.'),
    ];

    $form['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Name'),
      '#default_value' => '',
      '#required' => false,
      '#description' => $this->t('Name of the Material Design Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list.', [
        '@iconsLink' => Link::fromTextAndUrl(
          $this->t('the icon list'),
          Url::fromUri('https://material.io/resources/icons', ['attributes' => ['target' => '_blank']])
        )->toString(),
      ]),
      '#autocomplete_route_name' => 'material_icons.autocomplete',
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
    $form_values = $form_state->getValues();

    if (trim($form_values['dark_button_text']) == '') {
      return $response->addCommand(new HtmlCommand('#button-text', 'Please enter button text'));
    }
    if (trim($form_values['dark_button_link_type']) == '') {
      return $response->addCommand(new HtmlCommand('#link-type', 'Please select link type'));
    }
    if ($form_values['dark_button_link_type'] == 'content' && trim($form_values['dark_button_node_link']) == '') {
      return $response->addCommand(new HtmlCommand('#button-link', 'Please select content reference'));
    }
    if ($form_values['dark_button_link_type'] == 'document' && trim($form_values['dark_button_document_link']) == '') {
      return $response->addCommand(new HtmlCommand('#button-link-document', 'Please select document reference'));
    }

    $link_type = $form_state->getValue('dark_button_link_type');
    if ($link_type == 'content') {
      $node_id = $form_state->getValue('dark_button_node_link');
      $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node_id);
    } else {
      $document_id = $form_state->getValue('dark_button_document_link');
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($document_id);
      $fid = $media->get('field_media_document')->target_id;
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid)->getFileUri();
      $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file);
    }


    $values = [
      'settings' => [
        'button_type' => $form_state->getValue('dark_button_type'),
        'target' => $form_state->getValue('target'),
        'button_text' => $form_state->getValue('dark_button_text'),
        'button_link' => $url,
        'button_document_link' => $form_state->getValue('dark_button_document_link'),
        'icon' => $form_state->getValue('icon'),
      ],
    ];

    $response->addCommand(new EditorDialogSave($values));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
