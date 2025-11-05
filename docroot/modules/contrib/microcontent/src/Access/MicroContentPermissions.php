<?php

namespace Drupal\microcontent\Access;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\microcontent\Entity\MicroContentTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for micro-content of different types.
 */
final class MicroContentPermissions implements ContainerInjectionInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new MicroContentPermissions.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns an array of micro-content type permissions.
   *
   * @return array
   *   The micro-content type permissions.
   */
  public function getPermissions() {
    $permissions = [];
    // Generate micro-content permissions for all micro-content types.
    foreach ($this->entityTypeManager->getStorage('microcontent_type')->loadMultiple() as $type) {
      $permissions += $this->buildPermissions($type);
    }

    return $permissions;
  }

  /**
   * Returns a list of micro-content permissions for a given microcontent type.
   *
   * @param \Drupal\microcontent\Entity\MicroContentTypeInterface $type
   *   The microcontent type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(MicroContentTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id microcontent" => [
        'title' => new TranslatableMarkup('%type_name: Create new micro-content', $type_params),
      ],
      "update own $type_id microcontent" => [
        'title' => new TranslatableMarkup('%type_name: Edit own micro-content', $type_params),
      ],
      "update any $type_id microcontent" => [
        'title' => new TranslatableMarkup('%type_name: Edit any micro-content', $type_params),
      ],
      "delete own $type_id microcontent" => [
        'title' => new TranslatableMarkup('%type_name: Delete own micro-content', $type_params),
      ],
      "delete any $type_id microcontent" => [
        'title' => new TranslatableMarkup('%type_name: Delete any micro-content', $type_params),
      ],
    ];
  }

}
