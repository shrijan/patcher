<?php

declare(strict_types=1);

namespace Drupal\bca;

use Drupal\bca\Annotation\Bundle as BundleAnnotation;
use Drupal\bca\Attribute\Bundle as BundleAttribute;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Bundle plugin manager.
 */
class BundlePluginManager extends DefaultPluginManager {

  protected const SUBDIR = 'Entity';
  protected const CACHE_KEY = 'bca_bundle_classes';

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(static::SUBDIR, $namespaces, $module_handler, EntityInterface::class, BundleAttribute::class, BundleAnnotation::class);
    $this->setCacheBackend($cache_backend, static::CACHE_KEY);
  }

}
