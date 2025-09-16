<?php

namespace Drupal\dphi_components\Service;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

class NodesByMenuData {

  protected array $menuData = [];

  public function __construct(
    protected EntityRepositoryInterface $entityRepository,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MenuLinkTreeInterface $menuLinkTree
  ) {}

  public function getData(string $menuName = 'main'): array {
    $parameters = new MenuTreeParameters();
    $menuItems = $this->menuLinkTree->load($menuName, $parameters);

    // Iterate through menu items and fetch associated nodes recursively.
    foreach ($menuItems as $menuItem) {
      $this->processMenuItem($menuItem);
    }

    return $this->menuData;
  }

  protected function processMenuItem(MenuLinkTreeElement $menuElement): void {
    $link = $menuElement->link;

    $editUrl = '';
    $operations = $link->getOperations();
    if (array_key_exists('edit', $operations)) {
      $url = $operations['edit']['url'];
      $option = $url->getOption('query');
      if (!empty($option['destination']) && strtok($option['destination'], '?') == '/admin/content/content-tree/data') {
        $option['destination'] = '/admin/content/content-tree';
        $url->setOption('query', $option);
      }
      $editUrl = $url->toString();
    }

    $itemData = [
      'menuItem' => [
        'id' => $link->getPluginId(),
        'title' => $link->getTitle(),
        'url' => $link->getUrlObject()->toString(),
        'edit' => $editUrl,
        'weight' => (int) $link->getWeight(),
        'parent' => $link->getParent() ?: 0,
        'enabled' => $link->isEnabled(),
      ],
      'node' => [],
      'children' => [],
    ];

    if ($node = $this->getNodeForMenuItem($link)) {
      $itemData['node'] = [
        'id' => (int) $node->id(),
        'title' => $node->label(),
        'contentType' => $node->bundle(),
        'author' => $node->getOwner()->label(),
        'published' => $node->get('status')
          ->first()
          ->get('value')
          ->getValue(),
        'moderationState' => $node->get('moderation_state')
          ->first()
          ->get('value')
          ->getValue(),
        'lastUpdated' => (int) $node->get('changed')
          ->first()
          ->get('value')
          ->getValue(),
        'view' => $node->toUrl('canonical')->toString(),
        'edit' => $node->toUrl('edit-form')->toString(),
      ];
    }

    // Recursively process child menu items.
    if (!empty($menuElement->subtree)) {
      foreach ($menuElement->subtree as $childMenuItem) {
        $this->processMenuItem($childMenuItem);
      }
    }

    $this->menuData[] = $itemData;
  }

  protected function getNodeForMenuItem(MenuLinkInterface $link): ?NodeInterface {
    $url = $link->getUrlObject();
    if (!$url || $url->isExternal() || !$url->isRouted()) {
      return NULL;
    }

    $urlParameters = $url->getRouteParameters();
    if (!array_key_exists('node', $urlParameters)) {
      return NULL;
    }

    $nodeId = $urlParameters['node'];
    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    return $nodeStorage->load($nodeId);
  }

}
