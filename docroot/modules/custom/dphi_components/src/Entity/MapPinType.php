<?php

declare(strict_types=1);

namespace Drupal\dphi_components\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Map Pin type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "map_pin_type",
 *   label = @Translation("Map Pin type"),
 *   label_collection = @Translation("Map Pin types"),
 *   label_singular = @Translation("map pin type"),
 *   label_plural = @Translation("map pins types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count map pins type",
 *     plural = "@count map pins types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\dphi_components\Form\MapPinTypeForm",
 *       "edit" = "Drupal\dphi_components\Form\MapPinTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\dphi_components\MapPinTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer map_pin types",
 *   bundle_of = "map_pin",
 *   config_prefix = "map_pin_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/map_pin_types/add",
 *     "edit-form" = "/admin/structure/map_pin_types/manage/{map_pin_type}",
 *     "delete-form" = "/admin/structure/map_pin_types/manage/{map_pin_type}/delete",
 *     "collection" = "/admin/structure/map_pin_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class MapPinType extends ConfigEntityBundleBase {

  /**
   * The machine name of this map pin type.
   */
  protected string $id;

  /**
   * The human-readable name of the map pin type.
   */
  protected string $label;

}
