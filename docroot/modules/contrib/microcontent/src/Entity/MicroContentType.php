<?php

namespace Drupal\microcontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the microcontent type entity.
 *
 * @ConfigEntityType(
 *   id = "microcontent_type",
 *   label = @Translation("Micro-content type"),
 *   label_singular = @Translation("micro-content type"),
 *   label_plural = @Translation("micro-content types"),
 *   label_collection = @Translation("Micro-content types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count micro-content type",
 *     plural = "@count micro-content types"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\microcontent\EntityHandlers\MicrocontentTypeAccessHandler",
 *     "list_builder" = "Drupal\microcontent\EntityHandlers\MicrocontentTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\microcontent\Form\MicroContentTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer microcontent types",
 *   config_prefix = "type",
 *   bundle_of = "microcontent",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/microcontent-types/add",
 *     "delete-form" = "/admin/structure/microcontent-types/manage/{microcontent_type}/delete",
 *     "reset-form" = "/admin/structure/microcontent-types/manage/{microcontent_type}/reset",
 *     "overview-form" = "/admin/structure/microcontent-types/manage/{microcontent_type}/overview",
 *     "edit-form" = "/admin/structure/microcontent-types/manage/{microcontent_type}",
 *     "collection" = "/admin/structure/microcontent-types",
 *   },
 *   config_export = {
 *     "name",
 *     "id",
 *     "description",
 *     "type_class",
 *     "new_revision",
 *   }
 * )
 */
class MicroContentType extends ConfigEntityBundleBase implements MicroContentTypeInterface, EntityDescriptionInterface {

  /**
   * The pane set type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Name of the set type.
   *
   * @var string
   */
  protected $name;

  /**
   * Description of the microcontent type.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Type class.
   *
   * @var string
   */
  protected $type_class = '';

  /**
   * The default revision setting for microcontent of this type.
   *
   * @var bool
   */
  protected $new_revision = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() : string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeClass() : string {
    return $this->type_class;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

}
