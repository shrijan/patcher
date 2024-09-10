<?php

declare(strict_types = 1);

namespace Drupal\field_states_ui;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Manages field state plugins.
 *
 * @see hook_field_state_info_alter()
 * @see \Drupal\field_states_ui\Annotation\FieldState
 * @see \Drupal\field_states_ui\FieldStateInterface
 * @see \Drupal\field_states_ui\FieldStateBase
 * @see plugin_api
 */
class FieldStateManager extends DefaultPluginManager {

  /**
   * LoggerInterface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * Constructs a new FieldStateManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerInterface $logger) {
    parent::__construct('Plugin/FieldState', $namespaces, $module_handler, 'Drupal\field_states_ui\FieldStateInterface', 'Drupal\field_states_ui\Annotation\FieldState');

    $this->alterInfo('field_state_info');
    $this->setCacheBackend($cache_backend, 'field_state_plugins');
    $this->logger = $logger;
  }

  /**
   * Apply the field states to a form element.
   *
   * Due to various form elements having different array structure, the states
   * array has to be put in a different spot for different widgets. Most common
   * locations are `value` and `target_id` or for multiple value forms, on the
   * wrapper.
   *
   * @param mixed[] $element
   *   The field widget form elements as constructed by
   *   \Drupal\Core\Field\WidgetBase::formMultipleElements().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param mixed[] $context
   *   An associative array containing the following key-value pairs:
   *   - form: The form structure to which widgets are being attached. This may
   *     be a full form structure, or a sub-element of a larger form.
   *   - widget: The widget plugin instance.
   *   - items: The field values, as a
   *     \Drupal\Core\Field\FieldItemListInterface object.
   *   - default: A boolean indicating whether the form is being shown as a
   *     dummy form to set default values.
   *
   * @see hook_field_widget_multivalue_form_alter()
   */
  public function apply(array &$element, FormStateInterface $form_state, array $context) {
    /** @var \Drupal\Core\Field\PluginSettingsInterface $plugin */
    $plugin = $context['widget'];

    // Check that it is enabled for this field.
    if (empty($plugin->getThirdPartySettings('field_states_ui')['field_states'])) {
      return;
    }
    $states = $plugin->getThirdPartySettings('field_states_ui')['field_states'];

    /** @var \Drupal\Core\Field\FieldItemListInterface $field_item */
    $field_item       = $context['items'];
    $field_definition = $field_item->getFieldDefinition();
    $type             = $field_definition->getType();
    $plugin_id        = $plugin->getPluginId();

    // Handle the type of field appropriately.
    switch ($type) {

      case 'entity_reference':
        switch ($plugin_id) {
          case 'chosen_select':
          case 'inline_entity_form_simple':
          case 'inline_entity_form_complex':
          case 'ief_table_view_complex':
          case 'options_select':
          case 'options_buttons':
          case 'media_library_widget':
          case 'select2_entity_reference':
            $target = &$element;
            break;

          case 'entity_browser_entity_reference':
            if (!isset($element[0])) {
              $target = &$element;
            }
            else {
              $target = &$element[0];
            }
            break;

          case 'entity_reference_autocomplete':
          case 'entity_reference_autocomplete_tags':
            if (isset($element['#media_help'])) {
              $target = &$element;
              $container = TRUE;
            }
            elseif (!isset($element[0])) {
              $target = &$element['target_id'];
            }
            else {
              $target = &$element[0]['target_id'];
            }
            break;

          default:
            // Log a notice so that user(s) can report unrecognized field
            // plugin_id.
            $target = $this->logMissingType($type, $plugin_id, $element);
            break;
        }
        break;

      case 'entity_reference_revisions':
        switch ($plugin_id) {
          case 'entity_reference_revisions_autocomplete':
            if (!isset($element[0])) {
              $target = &$element['target_id'];
            }
            else {
              $target = &$element[0]['target_id'];
            }
            break;

          case 'entity_reference_paragraphs':
          case 'layout_paragraphs':
          case 'options_select':
          case 'options_buttons':
          case 'paragraphs':
            $target = &$element;
            break;

          default:
            // Log a notice so that user(s) can report unrecognized field
            // plugin_id.
            $target = $this->logMissingType($type, $plugin_id, $element);
            break;
        }
        break;

      case 'boolean':
        switch ($plugin_id) {
          case 'options_buttons':
            $target = &$element;
            break;

          case 'boolean_checkbox':
            $target = &$element['value'];
            break;

          default:
            $target = &$element[0]['value'];
            break;
        }
        break;

      case 'address_country':
      case 'decimal':
      case 'email':
      case 'float':
      case 'geofield':
      case 'integer':
      case 'string':
      case 'string_long':
      case 'telephone':
        $target = &$element[0]['value'];
        break;

      case 'text':
      case 'text_with_summary':
      case 'text_long':
      case 'list_float':
      case 'list_integer':
      case 'list_string':
      case 'link':
      case 'datetime':
      case 'color_field_type':
      case 'address_zone':
      case 'address':
        if (!isset($element[0])) {
          $target = &$element;
        }
        else {
          $target = &$element[0];
        }
        break;

      case 'daterange':
        switch ($plugin_id) {
          case 'smartdate_only':
          case 'smartdate_inline':
          case 'smartdate_default':
            $target = &$element[0];
            $target['#type'] = 'container';
            break;

          default:
            if (!isset($element[0])) {
              $target = &$element;
            }
            else {
              $target = &$element[0];
            }
        }
        break;

      case 'smartdate':
        $target = &$element[0];
        $target['#type'] = 'container';
        break;

      case 'created':
      case 'name':
      case 'timestamp':
      case 'youtube':
        $target = &$element[0];
        $container = TRUE;
        break;

      case 'file':
      case 'image':
      case 'markup':
      case 'viewsreference':
      case 'webform':
        $target = &$element;
        $container = TRUE;
        break;

      default:
        // Log a notice so that user(s) can report unrecognized field types.
        $target = $this->logMissingType($type, $plugin_id, $element);
        $container = TRUE;
        break;
    }

    if (isset($target['#field_parents'])) {
      $parents = $target['#field_parents'];
    }
    else {
      $parents = [];
      $this->logger->notice(
        t(
          '#field_parents key not found. This may cause problems. If so, please report on the @link. For quickest resolution, please include the element details: @details',
          [
            '@link' => Link::fromTextAndUrl(
              t('Field States UI Issue Queue'),
              Url::fromUri('https://www.drupal.org/project/issues/field_states_ui')
            )->toString(),
            '@details' => var_export(array_keys($element[0] ?? $element), TRUE),
          ]
        )
      );
    }
    if (isset($container) || (isset($element['#cardinality_multiple']) && $element['#cardinality_multiple'])) {
      // Multiple widget field or special field that needs a wrapper.
      // Add a container element and set states on that to ensure it works.
      // This increases divitis which is already common on Drupal forms but for
      // multiple value fields or some fields with multiple items this is the
      // best solution.
      if (isset($element['#type'])) {
        $element = [
          'element' => $element,
          '#type' => 'container',
          '#states' => $this->getStates($states, $form_state, $context, $element, $parents),
        ];
      }
      else {
        $element['#type'] = 'container';
        $element['#states'] = $this->getStates($states, $form_state, $context, $element, $parents);
      }
    }
    else {
      $target['#states'] = $this->getStates($states, $form_state, $context, $element, $parents);
    }
  }

  /**
   * Implements hook_field_widget_third_party_settings_form().
   */
  public function settingsForm(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, string $form_mode, array $form, FormStateInterface $form_state): array {
    $field_name = $field_definition->getName();
    $element = [
      'form' => [
        '#type' => 'fieldset',
        '#title' => t('Manage Field States'),
        '#description' => t('Configure field states - ie automatic hiding/showing of fields.'),
        'list' => [
          '#type' => 'table',
          '#header' => [
            t('Type'),
            t('Comparison'),
            t('Operations'),
          ],
          '#empty' => t('There are no field states applied to this field currently. Add one by selecting an option below.'),
        ],
      ],
    ];

    $cancel = [
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => [[self::class, 'settingsSubmit']],
      '#field_name' => $field_name,
      '#limit_validation_errors' => [],
      '#op' => 'cancel',
    ];

    // Display and maintain existing form states with edit options.
    $field_states_settings = $plugin->getThirdPartySettings('field_states_ui');
    if (!empty($field_states_settings['field_states'])) {
      $element['field_states'] = [];
      foreach ($field_states_settings['field_states'] as $key => $state) {
        if (!isset($state['id'])) {
          continue;
        }
        $field_state = $this->createInstance($state['id'], $state);
        $element['form']['list'][$key] = [
          'type' => [
            '#type' => 'markup',
            '#markup' => $field_state->label(),
          ],
          'comparison' => $field_state->getSummary(),
          'operations' => [
            'edit' => [
              '#type' => 'submit',
              '#value' => t('Edit'),
              '#op' => 'edit',
              '#submit' => [[self::class, 'settingsSubmit']],
              '#field_name' => $field_name,
              '#key' => $key,
              '#name' => 'edit-' . $key,
            ],
            'delete' => [
              '#type' => 'submit',
              '#value' => t('Delete'),
              '#op' => 'delete',
              '#submit' => [[self::class, 'settingsSubmit']],
              '#field_name' => $field_name,
              '#key' => $key,
              '#name' => 'delete-' . $key,
            ],
          ],
        ];
        $element['field_states'][$field_state->getUuid()] = [
          'uuid' => [
            '#type' => 'hidden',
            '#value' => $field_state->getUuid(),
          ],
          'id' => [
            '#type' => 'hidden',
            '#value' => $field_state->getPluginId(),
          ],
          'data' => $field_state->getConfigurationForForm(),
        ];
      }
    }

    // Provide form elements to edit/add form states.
    if ($form_state->get('field_states_ui_edit') === $field_name) {
      if ($form_state->get('field_states_ui_target')) {
        $target = $form_state->get('field_states_ui_target');
        $states = $plugin->getThirdPartySettings('field_states_ui')['field_states'];
        if (!isset($states[$target])) {
          return $element;
        }
        $type = $states[$target]['id'];
        $field_state = $this->createInstance($type, $states[$target]);
        $title = t('Edit field state: @type', ['@type' => $field_state->label()]);
        $submit_label = t('Update State');
        $op = 'process_update';
      }
      else {
        $type = $form_state->getValue([
          'fields',
          $field_name,
          'settings_edit_form',
          'third_party_settings',
          'field_states_ui',
          'form',
          'type',
        ]);
        $field_state = $this->createInstance($type);
        $title = t('Add new field state: @type', ['@type' => $field_state->label()]);
        $submit_label = t('Add');
        $op = 'new';
      }
      $element['form']['edit'] = $field_state->buildConfigurationForm([], $form_state);
      $element['form']['edit']['#type'] = 'fieldset';
      $element['form']['edit']['#title'] = $title;
      $element['form']['edit']['submit'] = [
        'save' => [
          '#type' => 'submit',
          '#value' => $submit_label,
          '#validate' => [[self::class, 'settingsValidate']],
          '#submit' => [[self::class, 'settingsSubmit']],
          '#field_name' => $field_name,
          '#op' => $op,
          '#plugin' => $type,
        ],
        'cancel' => $cancel,
      ];
    }

    // Provide form elements to confirm delete action.
    elseif ($form_state->get('field_states_ui_edit') === 'delete') {
      $element['form']['delete'] = [
        '#type' => 'fieldset',
        '#tree' => FALSE,
        '#title' => t('Delete field state?'),
        'delete_submit' => [
          '#type' => 'submit',
          '#value' => t('Confirm Delete'),
          '#submit' => [[self::class, 'settingsSubmit']],
          '#target' => $form_state->get('field_states_ui_target'),
          '#op' => 'process_delete',
          '#field_name' => $field_name,
        ],
        'cancel' => $cancel,
      ];
    }

    // Provide form elements to select a new form state type.
    else {
      $field_state_options = [];
      $field_states = $this->getDefinitions();
      foreach ($field_states as $field_state => $definition) {
        $field_state_options[$field_state] = $definition['label'];
      }
      $element['form']['type'] = [
        '#type' => 'select',
        '#title' => t('Field States'),
        '#title_display' => 'invisible',
        '#options' => $field_state_options,
        '#empty_option' => t('Select a new field state'),
      ];
      $element['form']['add'] = [
        '#type' => 'submit',
        '#value' => t('Add'),
        '#validate' => [[self::class, 'settingsValidate']],
        '#submit' => [[self::class, 'settingsSubmit']],
        '#field_name' => $field_name,
        '#op' => 'add',
      ];
    }
    return $element;
  }

  /**
   * Submit function to add/edit field states.
   *
   * @param mixed[] $form
   *   The whole EntityFormDisplay form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function settingsSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $field_name = $trigger['#field_name'];
    $form_state->set('plugin_settings_edit', $field_name);
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $entity */
    $entity = $form_object->getEntity();

    // Show the add new field state form for the specified field state type.
    if ($trigger['#op'] === 'add') {
      $form_state->set('field_states_ui_edit', $field_name);
    }

    // Cancel editing/creating/deleting a field state.
    elseif ($trigger['#op'] === 'cancel') {
      $form_state->set('field_states_ui_edit', NULL);
      $form_state->set('field_states_ui_target', NULL);
    }

    // Show the edit field state form for the selected field state.
    elseif ($trigger['#op'] === 'edit') {
      $form_state->set('field_states_ui_edit', $field_name);
      $form_state->set('field_states_ui_target', $trigger['#key']);
    }

    // Show confirm dialogue for form state deletion.
    elseif ($trigger['#op'] === 'delete') {
      $form_state->set('field_states_ui_edit', 'delete');
      $form_state->set('field_states_ui_target', $trigger['#key']);
    }

    // Process deleting a field state.
    elseif ($trigger['#op'] === 'process_delete') {
      $field = $entity->getComponent($field_name);
      unset($field['third_party_settings']['field_states_ui']['field_states'][$form_state->get('field_states_ui_target')]);
      $entity->setComponent($field_name, $field);
      $entity->save();
      $form_state->set('field_states_ui_edit', NULL);
      $form_state->set('field_states_ui_target', NULL);
    }

    // Add a new field state and save the field/entity.
    elseif ($trigger['#op'] === 'new') {
      $field = $entity->getComponent($field_name);
      $field_state = self::getManager()->createInstance($trigger['#plugin']);
      $field_state_data = $form_state->getValue([
        'fields',
        $field_name,
        'settings_edit_form',
        'third_party_settings',
        'field_states_ui',
        'form',
        'edit',
      ]);
      $field_state->submitConfigurationForm($form, (new FormState())->setValues($field_state_data));
      $field['third_party_settings']['field_states_ui']['field_states'][$field_state->getUuid()] = $field_state->getConfiguration();
      $entity->setComponent($field_name, $field);
      $entity->save();
      $form_state->set('field_states_ui_edit', NULL);
    }

    // Update a field state and save the field/entity.
    elseif ($trigger['#op'] === 'process_update') {
      $field = $entity->getComponent($field_name);
      $target = $form_state->get('field_states_ui_target');
      $field_state = self::getManager()->createInstance($trigger['#plugin'], $field['third_party_settings']['field_states_ui']['field_states'][$target]);
      $field_state_data = $form_state->getValue([
        'fields',
        $field_name,
        'settings_edit_form',
        'third_party_settings',
        'field_states_ui',
        'form',
        'edit',
      ]);
      $field_state->submitConfigurationForm($form, (new FormState())->setValues($field_state_data));
      $field['third_party_settings']['field_states_ui']['field_states'][$field_state->getUuid()] = $field_state->getConfiguration();
      $entity->setComponent($field_name, $field);
      $entity->save();
      $form_state->set('field_states_ui_edit', NULL);
      $form_state->set('field_states_ui_target', NULL);
    }

    $form_state->setRebuild();
  }

  /**
   * Validation function for adding/editing field states.
   *
   * @param mixed[] $form
   *   The whole EntityFormDisplay form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function settingsValidate(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $field_name = $trigger['#field_name'];
    $op = $trigger['#op'];

    if ($op === 'add') {
      $element = "fields][$field_name][settings_edit_form][third_party_settings][field_states_ui][form][type";
      $type = $form_state->getValue([
        'fields',
        $field_name,
        'settings_edit_form',
        'third_party_settings',
        'field_states_ui',
        'form',
        'type',
      ]);
      if (!$type) {
        $form_state->setErrorByName($element, t('You must select a field state to add.'));
      }
    }
  }

  /**
   * Log a message about missing type/widget.
   *
   * @param string $type
   *   The field type id.
   * @param string $widget
   *   The field widget plugin id.
   * @param mixed[] $element
   *   The element form array being processed.
   *
   * @return mixed[]
   *   A hopefully correct target section.
   */
  protected function logMissingType(string $type, string $widget, array $element): array {
    $this->logger->notice(
      t(
        'Field type: "@type" with widget "@widget" was unrecognized. Please report on the @link. For quickest resolution, please include the element details: @details and source module',
        [
          '@type' => $type,
          '@widget' => $widget,
          '@link' => Link::fromTextAndUrl(
            t('Field States UI Issue Queue'),
            Url::fromUri('https://www.drupal.org/project/issues/field_states_ui')
          )->toString(),
          '@details' => var_export(array_keys($element[0] ?? $element), TRUE),
        ]
      )
    );
    if (isset($element[0]['target_id'])) {
      $target = &$element[0]['target_id'];
    }
    elseif (isset($element['target_id'])) {
      $target = &$element['target_id'];
    }
    else {
      $target = &$element;
    }
    return $target;
  }

  /**
   * Returns the field states for a given element.
   *
   * @param mixed[] $field_states
   *   An array of field state configuration.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Provides a object for the current state of a form.
   * @param mixed[] $context
   *   An associative array containing the following key-value pairs:
   *   - form: The form structure to which widgets are being attached. This may
   *     be a full form structure, or a sub-element of a larger form.
   *   - widget: The widget plugin instance.
   *   - items: The field values, as a
   *     \Drupal\Core\Field\FieldItemListInterface object.
   *   - delta: The order of this item in the array of subelements (0, 1, etc).
   *   - default: A boolean indicating whether the form is being shown as a
   *     dummy form to set default values.
   * @param mixed[] $element
   *   The field widget form element as constructed by
   *   \Drupal\Core\Field\WidgetBaseInterface::form().
   * @param string[] $parents
   *   The current element's parents in the form.
   *
   * @return mixed[]
   *   An array of states to render.
   */
  protected function getStates(array $field_states, FormStateInterface $form_state, array $context, array $element, array $parents) {
    $states = [];
    foreach ($field_states as $state) {
      if (!isset($state['id'])) {
        continue;
      }
      try {
        /** @var \Drupal\field_states_ui\FieldStateInterface $field_state */
        $field_state = $this->createInstance($state['id'], $state);
      }
      catch (\Throwable $exception) {
        continue;
      }
      $field_state->applyState($states, $form_state, $context, $element, $parents);
    }
    return $states;
  }

  /**
   * Get the plugin manager.
   *
   * @return \Drupal\field_states_ui\FieldStateManager
   *   The plugin manager
   */
  protected static function getManager() {
    return \Drupal::service('plugin.manager.field_states_ui.fieldstate');
  }

}
