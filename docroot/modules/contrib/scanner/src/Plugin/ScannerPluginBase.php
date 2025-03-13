<?php

namespace Drupal\scanner\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\scanner\WordBoundariesHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for ScannerPluginBase class.
 */
abstract class ScannerPluginBase extends PluginBase implements ScannerPluginInterface {

  use StringTranslationTrait;

  /**
   * The temp store we use to store form values.
   *
   * @var Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * The scanner plugin manager.
   *
   * @var Drupal\scanner\Plugin\ScannerPluginManager
   */
  protected $scannerManager;

  /**
   * The scanner.word_boundaries_helper service.
   *
   * @var \Drupal\scanner\WordBoundariesHelper
   */
  protected $scannerHelper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a ScannerPluginBase object.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStore
   *   The temporary store.
   * @param \Drupal\scanner\Plugin\ScannerPluginManager $scannerManager
   *   The scanner manager.
   * @param \Drupal\scanner\WordBoundariesHelper $scannerHelper
   *   The scanner.word_boundaries_helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PrivateTempStoreFactory $tempStore,
    ScannerPluginManager $scannerManager,
    WordBoundariesHelper $scannerHelper,
    EntityTypeManagerInterface $entityTypeManager,
    DateFormatterInterface $date_formatter,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempStore = $tempStore;
    $this->scannerManager = $scannerManager;
    $this->scannerHelper = $scannerHelper;
    $this->entityTypeManager = $entityTypeManager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tempstore.private'),
      $container->get('plugin.manager.scanner'),
      $container->get('scanner.word_boundaries_helper'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Performs the search operation and returns the results.
   *
   * @param string $field
   *   The fully qualified name of the field (entityType:bundle:fieldname).
   * @param array $values
   *   The input values from the form ($form_state values).
   *
   * @return array
   *   An array containing the entity titles and an array of matches in the
   *   entity.
   */
  abstract public function search($field, array $values);

  /**
   * Performs the replace operation and returns the results;.
   *
   * @param string $field
   *   The fully qualified name of the field (entityType:bundle:fieldname).
   * @param array $values
   *   The input values from the form ($form_state values).
   * @param array $undo_data
   *   The array for data values.
   *
   * @return array
   *   An array containing both the old and new revision IDs for each affected
   *   entity.
   */
  abstract public function replace($field, array $values, array $undo_data);

  /**
   * Performs the undo operation.
   *
   * @param array $data
   *   An array containing the old and new revision id for the entity.
   */
  abstract public function undo(array $data);

}
