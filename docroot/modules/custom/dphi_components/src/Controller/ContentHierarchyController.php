<?php

namespace Drupal\dphi_components\Controller;

use Drupal\dphi_components\Service\NodesByMenuData;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Content Hierarchy routes.
 */
class ContentHierarchyController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    protected MenuLinkManagerInterface $menuLinkManager,
    protected NodesByMenuData $nodesByMenuData
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link'),
      $container->get('dphi_components.nodes'),
    );
  }

  public function nodesByMenuPage(): array {
    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'content-hierarchy-root',
        'data-menu-names' => json_encode($this->getTypeLabels('menu')),
        'data-content-types' => json_encode($this->getTypeLabels('node_type')),
        'data-moderation-states' => json_encode($this->getModerationStates()),
      ],
      '#attached' => [
        'library' => [
          'dphi_components/devExtreme.contentHierarchyView',
        ],
      ],
    ];

    return $build;
  }

  public function nodesByMenuData(Request $request): JsonResponse {
    $response = new JsonResponse();

    $menuName = $request->query->get('menuName') ?? 'main';
    $response->setData($this->nodesByMenuData->getData($menuName));

    return $response;
  }

  public function updateMenuData(Request $request): JsonResponse {
    $response = new JsonResponse();

    if (!$menuItems = json_decode($request->getContent(), TRUE)) {
      $response->setStatusCode(400, 'Invalid body');
      return $response;
    }

    foreach ($menuItems as $menuItem) {
      $valuesToUpdate = [
        'parent' => $menuItem['parent'],
        'weight' => $menuItem['weight'],
      ];
      $this->menuLinkManager->updateDefinition($menuItem['id'], $valuesToUpdate);
    }

    $response->setData('Success');
    return $response;
  }

  private function getTypeLabels(string $entityType): array {
    $bundles = [];
    $storage = $this->entityTypeManager()->getStorage($entityType);
    $ids = $storage->getQuery()->accessCheck()->execute();
    $types = $storage->loadMultiple($ids);
    foreach ($types as $type) {
      $bundles[] = ['id' => $type->id(), 'label' => $type->label()];
    }
    return $bundles;
  }

  private function getModerationStates(): array {
    $moderation_states = [];
    if (!$workflow = Workflow::load('editorial')) {
      return [];
    }
    $workflowPlugin = $workflow->getTypePlugin();
    if ($workflowPlugin->getPluginId() === 'content_moderation') {
      foreach ($workflowPlugin->getStates() as $state) {
        $moderation_states[] = [
          'id' => $state->id(),
          'label' => $state->label(),
        ];
      }
    }
    return $moderation_states;
  }

}
