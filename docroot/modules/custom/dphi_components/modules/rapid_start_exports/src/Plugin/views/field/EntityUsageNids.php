<?php

namespace Drupal\rapid_start_exports\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\rapid_start_exports\Service\EntityUsageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field that calculates entity usage.
 *
 * @ViewsField("entity_usage_nids")
 */
class EntityUsageNids extends FieldPluginBase {

  /**
   * The entity usage service.
   *
   * @var \Drupal\rapid_start_exports\Service\EntityUsageService
   */
  protected $entityUsageService;

  /**
   * Constructs a MediaUsageCalculation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rapid_start_exports\Service\EntityUsageService $entity_usage_service
   *   The entity usage service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityUsageService $entity_usage_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityUsageService = $entity_usage_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('rapid_start_exports.entity_usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Get the entity ID.
    $entity = $this->getEntity($values);

    // Calculate the usage.
    $usage = $this->entityUsageService->getSourceEntityIds($this->getEntityType(), $entity->id());
    if (!empty($usage)) {
      return implode(', ', array_keys($usage));
    }
  }

  /**
   * Returns the ID for a result row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return string
   *   The row ID, in the form ENTITY_TYPE:ENTITY_ID.
   */
  public function getRowId(ResultRow $row) {
    $entity = $this->getEntity($row);
    return $entity->getEntityTypeId() . ':' . $entity->id();
  }
}
