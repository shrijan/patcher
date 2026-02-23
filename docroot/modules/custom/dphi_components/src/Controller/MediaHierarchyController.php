<?php

namespace Drupal\dphi_components\Controller;

use Drupal\dphi_components\Service\MediaData;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Media Hierarchy routes.
 */
class MediaHierarchyController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    protected MediaData $mediaData) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dphi_components.media'),
    );
  }

  public function mediaByFolderPage(): array {
    $folders = $this->mediaData->mediaFolderData();

    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'media-hierarchy-root',
        'data-folders' => json_encode($folders),
      ],
      '#attached' => [
        'library' => [
          'dphi_components/devExtreme.mediaHierarchyView',
        ],
      ],
    ];

    return $build;
  }

  public function mediaByFolderData(Request $request): JsonResponse {
    $response = new JsonResponse();

    $folderId = $request->query->get('folderId') ?? 'root';
    $response->setData($this->mediaData->mediaData($folderId));

    return $response;
  }

  public function updateMediaData(Request $request): JsonResponse {
    $response = new JsonResponse();

    $data = json_decode($request->getContent(), true) ?? [];

    if (array_keys($data) !== ['mediaId', 'targetFolderId']) {
      $response->setStatusCode(400, 'Invalid body');
      return $response;
    }

    try {
      $this->mediaData->changeMediaItemFolder((int) $data['mediaId'], (string) $data['targetFolderId']);
    } catch (\Error $error) {
      $response->setStatusCode(403,);
      $response->setData(['error' => $error->getMessage()]);
      return $response;
    }

    $response->setData('Success');
    return $response;
  }

}
