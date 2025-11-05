<?php

namespace Drupal\dphi_components\Menu;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuTreeParameters;

class MenuParentFormSelector extends \Drupal\Core\Menu\MenuParentFormSelector {

  /**
   * {@inheritdoc}
   */
  public function parentSelectElement($menu_parent, $id = '', array $menus = NULL) {
    $options_cacheability = new CacheableMetadata();
    $options = $this->getParentSelectOptions($id, $menus, $options_cacheability);
    // If no options were found, there is nothing to select.
    if ($options) {
      $element = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'id' => 'menu-parent-selector-root',
        ],
        '#attached' => [
          'library' => ['dphi_components/theme'],
        ],
      ];
      if (!isset($options[$menu_parent])) {
        // The requested menu parent cannot be found in the menu anymore. Try
        // setting it to the top level in the current menu.
        [$menu_name] = explode(':', $menu_parent, 2);
        $menu_parent = $menu_name . ':';
      }
      if (isset($options[$menu_parent])) {
        // Only provide the default value if it is valid among the options.
        $element['#attributes']['data-menu-parent'] = $menu_parent;
        $menu_parent = $options[$menu_parent]['parentId'] ?? '';
        while (array_key_exists($menu_parent, $options)) {
          $options[$menu_parent]['expanded'] = 1;
          $menu_parent = $options[$menu_parent]['parentId'] ?? '';
        }
      }
      $element['#attributes']['data-menu-options'] = json_encode(array_values($options));
      $options_cacheability->applyTo($element);
      return $element;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getParentSelectOptions($id = '', array $menus = NULL, CacheableMetadata &$cacheability = NULL) {
    if (!isset($menus)) {
      $menus = $this->getMenuOptions();
    }

    $options = [];
    $depth_limit = $this->getParentDepthLimit($id);
    foreach ($menus as $menu_name => $menu_title) {
      $menuId = $menu_name . ':';
      $options[$menuId] = ['id' => $menuId, 'text' => $menu_title];

      $parameters = new MenuTreeParameters();
      $parameters->setMaxDepth($depth_limit);
      $tree = $this->menuLinkTree->load($menu_name, $parameters);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $this->menuLinkTree->transform($tree, $manipulators);
      $this->parentSelectOptionsTreeWalk($tree, $menu_name, '--', $options, $id, $depth_limit, $cacheability, $menuId);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function parentSelectOptionsTreeWalk(array $tree, $menu_name, $indent, array &$options, $exclude, $depth_limit, CacheableMetadata &$cacheability = NULL, $parentId = '') {
    foreach ($tree as $element) {
      if ($element->depth > $depth_limit) {
        // Don't iterate through any links on this level.
        break;
      }

      // Collect the cacheability metadata of the access result, as well as the
      // link.
      if ($cacheability) {
        $cacheability = $cacheability
          ->merge(CacheableMetadata::createFromObject($element->access))
          ->merge(CacheableMetadata::createFromObject($element->link));
      }

      // Only show accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      $link = $element->link;
      if ($link->getPluginId() != $exclude) {
        // Override here to remove the indentation.
        $title = Unicode::truncate($link->getTitle(), 30, TRUE, FALSE);
        // End override.
        if (!$link->isEnabled()) {
          $title .= ' (' . $this->t('disabled') . ')';
        }
        $id = $menu_name . ':' . $link->getPluginId();
        $options[$id] = ['id' => $id, 'text' => $title, 'parentId' => $parentId];
        if (!empty($element->subtree)) {
          $this->parentSelectOptionsTreeWalk($element->subtree, $menu_name, '', $options, $exclude, $depth_limit, $cacheability, $id);
        }
      }
    }
  }

}
