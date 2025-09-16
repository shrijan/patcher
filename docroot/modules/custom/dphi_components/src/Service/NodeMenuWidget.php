<?php

declare(strict_types=1);

namespace Drupal\dphi_components\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dphi_components\Menu\MenuParentFormSelector;

/**
 * Enables the node menu form widget.
 */
final class NodeMenuWidget {

  /**
   * Constructs a NodeMenuWidget object.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly MenuParentFormSelectorInterface $menuParentFormSelector,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
    private readonly MenuParentFormSelector $dphiComponentsParentFormSelector,
  ) {}

  /**
   * Adds menu item fields to the node form.
   *
   * Copied from menu_ui module so we can add an extra element and the data to
   * drive it.
   *
   * @see menu_ui_form_node_form_submit()
 */
  public function alterForm(&$form, FormStateInterface $form_state): void {

    $config = $this->configFactory->get('dphi_components.settings');
    if (!$config->get('menu_widget_enabled')) {
      return;
    }
    // Generate a list of possible parents (not including this link or descendants).
    // @todo This must be handled in a #process handler.
    $node = $form_state->getFormObject()->getEntity();
    $defaults = menu_ui_get_menu_link_defaults($node);
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $node->type->entity;
    $type_menus_ids = $node_type->getThirdPartySetting('menu_ui', 'available_menus', ['main']);
    if (empty($type_menus_ids)) {
      return;
    }
    /** @var \Drupal\system\MenuInterface[] $type_menus */
    $menuStorage = $this->entityTypeManager->getStorage('menu');
    $type_menus = $menuStorage->loadMultiple($type_menus_ids);
    $available_menus = [];
    foreach ($type_menus as $menu) {
      $available_menus[$menu->id()] = $menu->label();
    }
    if ($defaults['id']) {
      $default = $defaults['menu_name'] . ':' . $defaults['parent'];
    } else {
      $default = $node_type->getThirdPartySetting('menu_ui', 'parent', 'main:');
    }
    $parent_element = $this->menuParentFormSelector->parentSelectElement($default, $defaults['id'], $available_menus);
    // If no possible parent menu items were found, there is nothing to display.
    if (empty($parent_element)) {
      return;
    }

    $form['menu'] = [
      '#type' => 'details',
      '#title' => t('Menu settings'),
      '#access' => $this->currentUser->hasPermission('administer menu'),
      '#open' => (bool) $defaults['id'],
      '#group' => 'advanced',
      '#attached' => [
        'library' => ['menu_ui/drupal.menu_ui'],
      ],
      '#tree' => TRUE,
      '#weight' => -2,
      '#attributes' => ['class' => ['menu-link-form']],
    ];
    $form['menu']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Provide a menu link'),
      '#default_value' => (int) (bool) $defaults['id'],
    ];
    $form['menu']['link'] = [
      '#type' => 'container',
      '#parents' => ['menu'],
      '#states' => [
        'invisible' => [
          'input[name="menu[enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Populate the element with the link data.
    foreach (['id', 'entity_id'] as $key) {
      $form['menu']['link'][$key] = [
        '#type' => 'value',
        '#value' => $defaults[$key],
      ];
    }

    $form['menu']['link']['title'] = [
      '#type' => 'textfield',
      '#title' => t('Menu link title'),
      '#default_value' => $defaults['title'],
      '#maxlength' => $defaults['title_max_length'],
    ];

    $form['menu']['link']['description'] = [
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $defaults['description'],
      '#description' => t('Shown when hovering over the menu link.'),
      '#maxlength' => $defaults['description_max_length'],
    ];

    $form['menu']['link']['menu_parent'] = $parent_element;
    $form['menu']['link']['menu_parent']['#title'] = t('Parent link');
    $form['menu']['link']['menu_parent']['#attributes']['class'][] = 'menu-parent-select';

    $form['menu']['link']['weight'] = [
      '#type' => 'number',
      '#title' => t('Weight'),
      '#default_value' => $defaults['weight'],
      '#description' => t('Menu links with lower weights are displayed before links with higher weights.'),
    ];

    /**
     * Begin Content Hierarchy override.
     */
    $form['menu']['link']['hierarchy']['label'] = [
      '#type' => 'label',
      '#title' => 'Alternative parent selector (beta)',
    ];

    $parent = $form_state->getUserInput()['menu']['menu_parent'] ?? $default;
    $form['menu']['link']['hierarchy']['widget'] = $this->dphiComponentsParentFormSelector->parentSelectElement($parent, $defaults['id'], $available_menus);
    $form['menu']['link']['hierarchy']['widget']['#attached']['library'][] = 'dphi_components/devExtreme.menuParentSelector';

    /**
     * End Content Hierarchy override.
     */
    foreach (array_keys($form['actions']) as $action) {
      if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
        $form['actions'][$action]['#submit'][] = 'menu_ui_form_node_form_submit';
      }
    }

    $form['#entity_builders'][] = 'menu_ui_node_builder';
  }

}
