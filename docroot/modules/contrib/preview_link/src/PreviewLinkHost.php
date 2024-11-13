<?php

declare(strict_types = 1);

namespace Drupal\preview_link;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for relationships between preview links and entities they unlock.
 */
class PreviewLinkHost implements PreviewLinkHostInterface {

  /**
   * Preview link storage.
   *
   * @var \Drupal\preview_link\PreviewLinkStorageInterface
   */
  protected PreviewLinkStorageInterface $previewLinkStorage;

  /**
   * PreviewLinkHost constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The clock.
   */
  final public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
  ) {
    $this->previewLinkStorage = $entityTypeManager->getStorage('preview_link');
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewLinks(EntityInterface $entity): array {
    $ids = $this->previewLinkStorage->getQuery()
      ->accessCheck()
      ->condition('entities.target_type', $entity->getEntityTypeId())
      ->condition('entities.target_id', $entity->id())
      ->execute();
    return $this->previewLinkStorage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function isToken(EntityInterface $entity, array $tokens): bool {
    $count = $this->previewLinkStorage->getQuery()
      ->accessCheck()
      ->condition('entities.target_type', $entity->getEntityTypeId())
      ->condition('entities.target_id', $entity->id())
      ->condition('token', $tokens, 'IN')
      ->count()
      ->execute();
    return $count > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPreviewLinks(EntityInterface $entity): bool {
    $count = $this->previewLinkStorage->getQuery()
      ->accessCheck()
      ->condition('entities.target_type', $entity->getEntityTypeId())
      ->condition('entities.target_id', $entity->id())
      ->condition('expiry', $this->time->getRequestTime(), '>')
      ->count()
      ->execute();
    return $count > 0;
  }

}
