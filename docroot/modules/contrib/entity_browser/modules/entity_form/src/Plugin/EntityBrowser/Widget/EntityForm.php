<?php

namespace Drupal\entity_browser_entity_form\Plugin\EntityBrowser\Widget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use function array_flip;

/**
 * Provides entity form widget.
 *
 * @EntityBrowserWidget(
 *   id = "entity_form",
 *   label = @Translation("Entity form"),
 *   description = @Translation("Provides entity form widget."),
 *   auto_select = FALSE
 * )
 */
class EntityForm extends WidgetBase {

  const ALLOW_EDITOR_BUNDLE_SELECTION = '__editor_bundle';

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs widget plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(parent::defaultConfiguration(), [
      'submit_text' => $this->t('Save entity'),
      'entity_type' => NULL,
      'bundle' => NULL,
      'form_mode' => 'default',
      'allowed_bundles' => [],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    if (empty($this->configuration['entity_type']) || empty($this->configuration['bundle'])  || empty($this->configuration['form_mode'])) {
      return ['#markup' => $this->t('The settings for this widget (Entity type, Bundle or Form mode) are not configured correctly.')];
    }

    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $bundle = $this->configuration['bundle'];
    $entity_type_id = $this->configuration['entity_type'];
    $allowed_bundles = $this->configuration['allowed_bundles'];
    if ($bundle === self::ALLOW_EDITOR_BUNDLE_SELECTION && ($form_state->get('step') !== 'inline_entity_form' || $form_state->get('entity_type') !== $entity_type_id) && count($allowed_bundles) > 1) {
      $definitions = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      $bundles = array_map(function ($item) {
        return $item['label'];
      }, $definitions);
      $title = $this->t('Bundle');
      if ($bundle_type = $this->entityTypeManager->getDefinition($entity_type_id)->getBundleEntityType()) {
        $title = $this->entityTypeManager->getDefinition($bundle_type)->getLabel();
      }
      $form['bundle'] = [
        '#type' => 'select',
        '#options' => array_intersect_key($bundles, array_flip($allowed_bundles)),
        '#title' => $title,
        '#required' => TRUE,
      ];
      $form['actions']['submit']['#value'] = $this->t('Continue');
      $form['actions']['submit']['#eb_widget_main_submit'] = FALSE;
      return $form;
    }

    // Pretend to be IEFs submit button.
    $form['#submit'] = [['Drupal\inline_entity_form\ElementSubmit', 'trigger']];
    $form['actions']['submit']['#ief_submit_trigger'] = TRUE;
    $form['actions']['submit']['#ief_submit_trigger_all'] = TRUE;

    if ($bundle === self::ALLOW_EDITOR_BUNDLE_SELECTION) {
      $bundle = reset($allowed_bundles);
      if ($form_state->get('step') === 'inline_entity_form') {
        $bundle = $form_state->get('bundle');
      }
    }

    $form['inline_entity_form'] = [
      '#type' => 'inline_entity_form',
      '#op' => 'add',
      '#entity_type' => $entity_type_id,
      '#bundle' => $bundle,
      '#form_mode' => $this->configuration['form_mode'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    return [$form[$form['#browser_parts']['widget']]['inline_entity_form']['#entity']];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('entity_type') !== $this->configuration['entity_type']) {
      $form_state->set('step', 'bundle_select ');
      return;
    }
    if ($form_state->get('step') !== 'inline_entity_form') {
      // Skip form validation if no bundle was selected.
      return;
    }

    parent::validate($form, $form_state); // TODO: Change the autogenerated stub
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#eb_widget_main_submit'])) {
      $entities = $this->prepareEntities($form, $form_state);
      array_walk(
        $entities,
        function (EntityInterface $entity) {
          $entity->save();
        }
      );
      $this->selectEntities($entities, $form_state);
      return;
    }
    if ($this->configuration['bundle'] === self::ALLOW_EDITOR_BUNDLE_SELECTION && $triggering_element_string = (string) $triggering_element['#value']) {
      if ($triggering_element_string === (string) $this->t('Continue') && $form_state->get('step') !== 'inline_entity_form') {
        $form_state->set('step', 'inline_entity_form');
        // Set the bundle as a FormState property. Since we are rebuilding
        // the form, submitted values will no persist across page requests.
        $form_state->set('bundle', $form_state->getValue('bundle'));

        $form_state->set('entity_type', $this->configuration['entity_type']);
        $form_state->setRebuild(TRUE);
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $parents = ['table', $this->uuid(), 'form'];
    $entity_type = $form_state->hasValue(array_merge($parents, ['entity_type'])) ? $form_state->getValue(array_merge($parents, ['entity_type'])) : $this->configuration['entity_type'];
    $bundle = $form_state->hasValue(array_merge($parents, ['bundle', 'select'])) ? $form_state->getValue(array_merge($parents, ['bundle', 'select'])) : $this->configuration['bundle'];
    $allowed_bundles = $form_state->hasValue(array_merge($parents, ['bundle', 'allowed_bundles'])) ? $form_state->getValue(array_merge($parents, ['bundle', 'allowed_bundles'])) : $this->configuration['allowed_bundles'];
    $form_mode = $form_state->hasValue(array_merge($parents, ['form_mode', 'form_select'])) ? $form_state->hasValue(array_merge($parents, ['form_mode', 'form_select'])) : $this->configuration['form_mode'];

    $definitions = $this->entityTypeManager->getDefinitions();
    $entity_types = array_combine(
      array_keys($definitions),
      array_map(function (EntityTypeInterface $item) {
        return $item->getLabel();
      }, $definitions)
    );

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $entity_types,
      '#default_value' => $entity_type,
      '#ajax' => [
        'callback' => [$this, 'updateFormElements'],
      ],
    ];

    $bundles = [];
    if ($entity_type) {
      $definitions = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      $bundles = array_map(function ($item) {
        return $item['label'];
      }, $definitions);
    }

    $form['bundle'] = [
      '#type' => 'container',
      'select' => [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $bundles,
        '#empty_option' => $this->t('Allow the editor to select'),
        '#empty_value' => self::ALLOW_EDITOR_BUNDLE_SELECTION,
        '#default_value' => $bundle,
      ],
      'allowed_bundles' => [
        '#states' => [
          'visible' => [
            sprintf(':input[name="table[%s][form][bundle][select]"]', $this->uuid()) => ['value' => self::ALLOW_EDITOR_BUNDLE_SELECTION],
          ],
        ],
        '#type' => 'checkboxes',
        '#title' => $this->t('Allowed bundles'),
        '#options' => $bundles,
        '#default_value' => $allowed_bundles,
      ],
      '#attributes' => ['id' => 'bundle-wrapper-' . $this->uuid()],
    ];

    $form['form_mode'] = [
      '#type' => 'container',
      'form_select' => [
        '#type' => 'select',
        '#title' => $this->t('Form mode'),
        '#default_value' => $form_mode,
        '#options' => $this->entityDisplayRepository->getFormModeOptions($entity_type),
      ],
      '#attributes' => ['id' => 'form-mode-wrapper-' . $this->uuid()],
    ];

    return $form;
  }

  /**
   * AJAX callback for bundle dropdown update.
   */
  public function updateBundle($form, FormStateInterface $form_state) {
    return $form['widgets']['table'][$this->uuid()]['form']['bundle'];
  }

  /**
   * AJAX callback for the Form Mode dropdown update.
   */
  public function updateFormMode($form, FormStateInterface $form_state) {
    return $form['widgets']['table'][$this->uuid()]['form']['form_mode'];
  }

  /**
   * AJAX callback to update the two form elements: bundle and form_mode.
   */
  public function updateFormElements($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#bundle-wrapper-' . $this->uuid(), $this->updateBundle($form, $form_state)));
    $response->addCommand(new ReplaceCommand('#form-mode-wrapper-' . $this->uuid(), $this->updateFormMode($form, $form_state)));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['allowed_bundles'] = [];
    if ($this->configuration['bundle']['select'] === self::ALLOW_EDITOR_BUNDLE_SELECTION) {
      $this->configuration['allowed_bundles'] = array_keys(array_filter($this->configuration['bundle']['allowed_bundles']));
    }
    $this->configuration['bundle'] = $this->configuration['bundle']['select'];
    $this->configuration['form_mode'] = $this->configuration['form_mode']['form_select'];
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    $accessControlHandler = $this->entityTypeManager->getAccessControlHandler($this->configuration['entity_type']);
    if ($this->configuration['bundle'] === self::ALLOW_EDITOR_BUNDLE_SELECTION) {
      $result = NULL;
      foreach ($this->configuration['allowed_bundles'] as $bundle) {
        if (!$result) {
          $result = $accessControlHandler->createAccess($bundle, NULL, [], TRUE);
          continue;
        }
        $result->orIf($accessControlHandler->createAccess($bundle, NULL, [], TRUE));
      }
      return $result;
    }
    return $accessControlHandler->createAccess($this->configuration['bundle'], NULL, [], TRUE);
  }

}
