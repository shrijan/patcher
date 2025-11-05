<?php

namespace Drupal\linky\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\link\LinkItemInterface;
use Drupal\linky\LinkyInterface;
use Drupal\linky\Url;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Linky entity.
 *
 * @ingroup linky
 *
 * @ContentEntityType(
 *   id = "linky",
 *   label = @Translation("Managed Link"),
 *   handlers = {
 *     "view_builder" = "Drupal\linky\LinkyEntityViewBuilder",
 *     "list_builder" = "Drupal\linky\LinkyListBuilder",
 *     "views_data" = "Drupal\linky\Entity\LinkyViewsData",
 *     "form" = {
 *       "default" = "Drupal\linky\Form\LinkyForm",
 *       "add" = "Drupal\linky\Form\LinkyForm",
 *       "edit" = "Drupal\linky\Form\LinkyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "access" = "Drupal\linky\LinkyAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\linky\LinkyHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "linky",
 *   revision_table = "linky_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer linky entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "link__title",
 *     "langcode" = "langcode",
 *     "owner" = "user_id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_default" = "revision_default",
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/linky/{linky}",
 *     "revision" = "/admin/content/linky/{linky}/revision/{linky_revision}/view",
 *     "add-form" = "/admin/content/linky/add",
 *     "edit-form" = "/admin/content/linky/{linky}/edit",
 *     "delete-form" = "/admin/content/linky/{linky}/delete",
 *     "collection" = "/admin/content/linky",
 *     "revision-delete-form" = "/admin/content/linky/{linky}/revision/{linky_revision}/delete",
 *     "revision-revert-form" = "/admin/content/linky/{linky}/revision/{linky_revision}/revert",
 *     "version-history" = "/admin/content/linky/{linky}/revisions",
 *   },
 *   field_ui_base_route = "entity.linky.admin"
 * )
 */
class Linky extends ContentEntityBase implements LinkyInterface {
  use EntityOwnerTrait;
  use EntityChangedTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastCheckedTime() {
    return $this->get('checked')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastCheckedTime($timestamp) {
    $this->get('checked')->value = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    $fields['id']->setDescription(new TranslatableMarkup('The ID of the Managed Link entity.'));
    $fields['uuid']->setDescription(new TranslatableMarkup('The UUID of the Managed Link entity.'));
    $fields['revision_log']->setDisplayConfigurable('form', TRUE);

    $fields['user_id']->setLabel(new TranslatableMarkup('Authored by'))
      ->setDescription(new TranslatableMarkup('The user ID of author of the Managed Link entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(new TranslatableMarkup('Link'))
      ->setDescription(new TranslatableMarkup('The location this managed link points to.'))
      ->setRequired(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
        'title' => DRUPAL_REQUIRED,
      ])
      ->setDisplayOptions('view', [
        'type' => 'link',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -2,
      ])
      ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the entity was last edited.'))
      ->setRevisionable(TRUE);

    $fields['checked'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Last checked'))
      ->setDescription(new TranslatableMarkup('The time that the link was last checked.'))
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->link->title . ' (' . $this->link->uri . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    $internalCanonical = parent::toUrl($rel, $options);
    if ($rel === 'canonical') {
      $options['linky_entity_canonical'] = $internalCanonical;
      try {
        return Url::fromUri($this->link->uri, $options);
      }
      catch (\InvalidArgumentException $exception) {
        // Re-create exception as one that the interface allows.
        throw new EntityMalformedException($exception->getMessage(), $exception->getCode(), $exception);
      }
    }
    return $internalCanonical;
  }

  /**
   * {@inheritdoc}
   */
  public function toLink($text = NULL, $rel = 'canonical', array $options = []) {
    if (!isset($text)) {
      return parent::toLink($this->link->title, $rel, $options);
    }
    return parent::toLink($text, $rel, $options);
  }

}
