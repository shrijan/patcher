<?php

namespace Drupal\microcontent\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines a class for micro-content entities.
 *
 * @ContentEntityType(
 *   id = "microcontent",
 *   label = @Translation("Micro-content"),
 *   handlers = {
 *     "view_builder" = "Drupal\microcontent\EntityHandlers\MicrocontentViewBuilder",
 *     "list_builder" = "Drupal\microcontent\EntityHandlers\MicrocontentListBuilder",
 *     "views_data" = "Drupal\microcontent\EntityHandlers\MicrocontentViewsData",
 *     "form" = {
 *       "default" = "Drupal\microcontent\Form\MicroContentForm",
 *       "add" = "Drupal\microcontent\Form\MicroContentForm",
 *       "edit" = "Drupal\microcontent\Form\MicroContentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\microcontent\Access\MicroContentAccessHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\microcontent\EntityHandlers\RouteBuilder",
 *     },
 *     "moderation" = "Drupal\microcontent\EntityHandlers\MicrocontentModerationHandler"
 *   },
 *   base_table = "microcontent",
 *   data_table = "microcontent_field_data",
 *   revision_table = "microcontent_revision",
 *   revision_data_table = "microcontent_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "published" = "status",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   bundle_entity_type = "microcontent_type",
 *   field_ui_base_route = "entity.microcontent_type.edit_form",
 *   common_reference_target = TRUE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer microcontent",
 *   links = {
 *     "canonical" = "/admin/content/microcontent/{microcontent}/edit",
 *     "delete-form" = "/admin/content/microcontent/{microcontent}/delete",
 *     "edit-form" = "/admin/content/microcontent/{microcontent}/edit",
 *     "create" = "/microcontent",
 *     "add-page" = "/admin/content/microcontent/add",
 *     "add-form" = "/admin/content/microcontent/add/{microcontent_type}",
 *     "collection" = "/admin/content/microcontent",
 *   }
 * )
 */
class MicroContent extends EditorialContentEntityBase implements MicroContentInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Set default revision_user.
    $fields[$entity_type->getRevisionMetadataKey('revision_user')]->setDefaultValueCallback(static::class . '::getDefaultEntityOwner');

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid']
      ->setLabel(t('Author'))
      ->setDescription(t('The username of the content author.'))
      ->setRevisionable(TRUE)
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
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the node was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the node was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['revision_log']->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
