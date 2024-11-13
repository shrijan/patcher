<?php

declare(strict_types = 1);

namespace Drupal\preview_link;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal hooks.
 */
class PreviewLinkHooks implements ContainerInjectionInterface {

  /**
   * PreviewLinkHooks constructor.
   *
   * @param \Drupal\preview_link\PreviewLinkStorageInterface $previewLinkStorage
   *   Preview link storage.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   */
  final public function __construct(
    protected PreviewLinkStorageInterface $previewLinkStorage,
    protected TimeInterface $time,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('preview_link'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Implements hook_cron().
   *
   * Cleans up expired Preview Links.
   *
   * @see \preview_link_cron()
   */
  public function cron(): void {
    $ids = $this->previewLinkStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('expiry', $this->time->getRequestTime(), '<')
      ->execute();

    // If there are no expired links then nothing to do.
    if (!count($ids)) {
      return;
    }

    $previewLinks = $this->previewLinkStorage->loadMultiple($ids);
    // Simply delete the preview links. A new one will be regenerated at a later
    // date as required.
    $this->previewLinkStorage->delete($previewLinks);
  }

}
