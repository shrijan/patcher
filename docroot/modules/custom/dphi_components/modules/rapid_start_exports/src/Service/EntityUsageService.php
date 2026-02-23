<?php

namespace Drupal\rapid_start_exports\Service;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_usage\EntityUsageInterface;

class EntityUsageService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EntityUsage service.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected $entityUsage;

  /**
   * Constructs a new EntityUsageService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The EntityUsage service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityUsageInterface $entity_usage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityUsage = $entity_usage;
  }

  /**
   * Gets an array of all the IDs, types, and bundles of the source entities
   * where the given entity is attached.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return array
   *   An array of source entity information.
   */
  public function getSourceEntityIds(string $entity_type, int $entity_id) {
    $source_entities = [];
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);

    if ($entity) {
      $usages = $this->entityUsage->listSources($entity);
      foreach ($usages as $source_type => $ids) {
        foreach ($ids as $source_id => $records) {
          $source_entity = $this->entityTypeManager->getStorage($source_type)->load($source_id);
          // if the source entity is a paragraph, get the parent node ID.
          if ($source_type == 'paragraph' || $source_type == 'block_content') {
            $source_entity = $this->getSourceEntity($source_entity);
          }
          if ($source_entity) {
            $source_entities[$source_id] =  $source_entity->id();
          }
        }
      }
    }

    return $source_entities;
  }


  /**
   * Retrieve the source entity.
   *
   * Note that some entities are special-cased, since they don't have canonical
   * templates and aren't expected to be re-usable. For example, if the entity
   * passed in is a paragraph or a block content, the method will return its
   * parent (host) entity instead.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The source entity or its parent entity, or NULL if not found.
   */
  protected function getSourceEntity(EntityInterface $source_entity) {
    if ($source_entity->getEntityTypeId() == 'paragraph') {
      $parent = $source_entity->getParentEntity();
      if ($parent) {
        return $this->getSourceEntity($parent);
      }
    }
    elseif ($source_entity->getEntityTypeId() === 'block_content') {
      $sources = $this->entityUsage->listSources($source_entity, FALSE);
      $source = reset($sources);
      if ($source !== FALSE) {
        $parent = $this->entityTypeManager->getStorage($source['source_type'])->load($source['source_id']);
        if ($parent) {
          return $this->getSourceEntity($parent);
        }
      }
    }
    return $source_entity;
  }
}
