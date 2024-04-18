<?php

declare(strict_types = 1);

namespace Drupal\preview_link\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\preview_link\PreviewLinkHostInterface;

/**
 * Preview link access check.
 */
class PreviewLinkAccessCheck implements AccessInterface {

  /**
   * PreviewLinkAccessCheck constructor.
   */
  public function __construct(
    protected PreviewLinkHostInterface $previewLinkHost,
  ) {
  }

  /**
   * Checks access to the preview link.
   */
  public function access(EntityInterface $entity = NULL, string $preview_token = NULL): AccessResultInterface {
    $neutral = AccessResult::neutral()
      ->addCacheableDependency($entity)
      ->addCacheContexts(['preview_link_route']);
    if (!$preview_token || !$entity) {
      return $neutral;
    }
    $account = \Drupal::currentUser();

    // If we can't find a valid preview link then ignore this.
    if (!$this->previewLinkHost->hasPreviewLinks($entity) or !($account->isAuthenticated())) {
      return $neutral->setReason('This entity does not have a preview link.');
    }

    // If an entity has a preview link and it doesnt match up, then explicitly
    // deny access.
    if (!$this->previewLinkHost->isToken($entity, [$preview_token])) {
      return AccessResult::forbidden('Preview token is invalid.')
        ->addCacheableDependency($entity)
        ->addCacheContexts(['preview_link_route']);
    }

    return AccessResult::allowed()
      ->addCacheableDependency($entity)
      ->addCacheContexts(['preview_link_route']);
  }

}
