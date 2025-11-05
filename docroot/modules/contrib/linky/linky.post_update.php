<?php

/**
 * @file
 * Post update functions for Linky.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Convert Linky entities to be revisionable.
 */
function linky_post_update_make_linky_revisionable(&$sandbox) {
  $entityTypeId = 'linky';
  $definitionUpdateManager = \Drupal::entityDefinitionUpdateManager();
  $entityType = $definitionUpdateManager->getEntityType($entityTypeId);
  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $lastInstalledSchemaRepository */
  $lastInstalledSchemaRepository = \Drupal::service('entity.last_installed_schema.repository');

  // Update entity type definition.
  $entityType->set('revision_table', 'linky_revision');
  $entityType->set('show_revision_ui', TRUE);
  $keys = $entityType->getKeys();
  $keys['owner'] = 'user_id';
  $keys['revision'] = 'revision_id';
  unset($keys['uid']);
  unset($keys['status']);
  $entityType->set('entity_keys', $keys);
  $entityType->set('revision_metadata_keys', [
    'revision_default' => 'revision_default',
    'revision_user' => 'revision_uid',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log',
  ]);

  // Add new fields.
  $fieldStorageDefinitions = $lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entityTypeId);

  // Add revision fields.
  $fieldStorageDefinitions['revision_id'] = BaseFieldDefinition::create('integer')
    ->setName('revision_id')
    ->setLabel(new TranslatableMarkup('Revision ID'))
    ->setTargetEntityTypeId($entityTypeId)
    ->setTargetBundle(NULL)
    ->setReadOnly(TRUE)
    ->setSetting('unsigned', TRUE);

  // Normally defined by EntityFieldManager.
  $fieldStorageDefinitions['revision_default'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_default')
    ->setLabel(new TranslatableMarkup('Default revision'))
    ->setDescription(new TranslatableMarkup('A flag indicating whether this was a default revision when it was saved.'))
    ->setTargetEntityTypeId($entityTypeId)
    ->setTargetBundle(NULL)
    ->setStorageRequired(TRUE)
    ->setInternal(TRUE)
    ->setTranslatable(FALSE)
    ->setRevisionable(TRUE);

  // Add revision author field.
  $fieldStorageDefinitions['revision_uid'] = BaseFieldDefinition::create('entity_reference')
    ->setName('revision_uid')
    ->setLabel(new TranslatableMarkup('Revision user'))
    ->setDescription(new TranslatableMarkup('The user ID of the author of the current revision.'))
    ->setTargetEntityTypeId($entityTypeId)
    ->setTargetBundle(NULL)
    ->setSetting('target_type', 'user')
    ->setRevisionable(TRUE);

  // Add revision created date field.
  // Cannot copy from other field because complaints of mismatched field types:
  // 'created' versus 'changed'.
  $fieldStorageDefinitions['revision_created'] = BaseFieldDefinition::create('created')
    ->setName('revision_created')
    ->setLabel(new TranslatableMarkup('Revision create time'))
    ->setDescription(new TranslatableMarkup('The time that the current revision was created.'))
    ->setTargetEntityTypeId($entityTypeId)
    ->setTargetBundle(NULL)
    ->setRevisionable(TRUE);

  // Add revision log field.
  $fieldStorageDefinitions['revision_log'] = BaseFieldDefinition::create('string_long')
    ->setName('revision_log')
    ->setLabel(t('Revision log message'))
    ->setDescription(t('Briefly describe the changes you have made.'))
    ->setTargetEntityTypeId($entityTypeId)
    ->setTargetBundle(NULL)
    ->setRevisionable(TRUE)
    ->setDefaultValue('')
    ->setDisplayOptions('form', [
      'type' => 'string_textarea',
      'weight' => 25,
      'settings' => [
        'rows' => 4,
      ],
    ])
    ->setDisplayConfigurable('form', TRUE);

  // Update existing fields.
  $fieldStorageDefinitions['changed']->setRevisionable(TRUE);
  $fieldStorageDefinitions['link']->setRevisionable(TRUE);
  $fieldStorageDefinitions['langcode']->setRevisionable(TRUE);

  $definitionUpdateManager->updateFieldableEntityType($entityType, $fieldStorageDefinitions, $sandbox);

  return new TranslatableMarkup('Managed Links converted to revisionable.');
}

/**
 * Copies values from base table to revision table.
 */
function linky_post_update_set_default_revisionable_data() {
  // BaseFieldDefinition::setInitialValueFromField() only works during a
  // "regular" field install operation, not during entity schema migrations like
  // we're doing here.
  \Drupal::database()->query('UPDATE {linky_revision} r
LEFT JOIN {linky} base ON base.id=r.id
SET
r.revision_created = r.changed,
r.revision_uid = base.user_id');

  return new TranslatableMarkup('Copied values from Managed Link base table to revision table.');
}
