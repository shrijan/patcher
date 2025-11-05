<?php

namespace Drupal\dphi_components\Plugin\Block;

use Drupal\menu_block\Plugin\Block\MenuBlock;

/**
 * Provides an extended Menu block.
 *
 * @Block(
 *   id = "sidebar_menu_block",
 *   admin_label = @Translation("Sidebar menu block"),
 *   category = @Translation("Menus"),
 *   deriver = "Drupal\dphi_components\Plugin\Derivative\SidebarMenuBlock",
 *   forms = {
 *     "settings_tray" = "\Drupal\system\Form\SystemMenuOffCanvasForm",
 *   },
 * )
 */
class SidebarMenuBlock extends MenuBlock {

  public function build() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node || !$node->hasField('field_sidebar_setting')) {
      return [];
    }

    $sidebarSetting = $node->get('field_sidebar_setting')->getString();
    if (!$sidebarSetting || !in_array($sidebarSetting, ['local', 'global'])) {
      return [];
    }

    $this->configuration['label_display'] = TRUE;

    if ($sidebarSetting == 'global') {
      if ($node->hasField('field_sidebar_level')) {
        $sidebarLevel = $node->get('field_sidebar_level')->getString() || 1;
        $menu_name = $this->getDerivativeId();
        $activeTrail = array_values($this->menuActiveTrail->getActiveTrailIds($menu_name));
        $this->configuration['parent'] = $activeTrail[count($activeTrail) - $sidebarLevel - 1];
        $this->configuration['label_type'] = self::LABEL_FIXED;
      }
      $this->configuration['depth'] = 0;
      $this->configuration['follow'] = FALSE;
    }
    else {
      $this->configuration['level'] = 1;
      $this->configuration['depth'] = 2;
      $this->configuration['follow'] = TRUE;
      $this->configuration['follow_parent'] = 'active';
      $this->configuration['label_type'] = self::LABEL_PARENT;
    }

    $this->configuration['expand'] = TRUE;
    $this->configuration['label_link'] = TRUE;
    $this->configuration['suggestion'] = 'sidebar';

    return parent::build();
  }

}
