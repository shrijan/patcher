<?php

namespace Drupal\dphi_components\Service;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class EntityDeleteFormAlter {

  public static function alterEntityDeleteFormMessages(&$form, FormStateInterface $form_state): void {
    /** @var EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    // Only act on delete forms.
    if (!self::isDeleteForm($form_object)) {
      return;
    }

    $entity = $form_object->getEntity();
    if (array_key_exists('entity_usage_delete_warning', $form)) {
      self::alterUsageWarning($form, $entity);
    }
    else {
      self::alterTrashMessage($form, $entity);
    }
  }

  protected static function alterUsageWarning(&$form, $entity): void {
    $activeUsages = self::countActiveUsages($entity);

    if ($activeUsages > 0) {
      unset($form['description']);
      $form['#attached']['drupalSettings']['mediaFileDelete']['usageDataAvailable'] = TRUE;
      $form['entity_usage_delete_warning'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            t('There are <a href="@usage_url" target="_blank">@usage_count recorded usages</a> of this asset in active use on this website. Please use the link provided to remove or unpublish all usages of this asset, then try again.', [
              '@usage_count' => $activeUsages,
              '@usage_url' => Url::fromRoute('entity_usage.usage_list', [
                'entity_type' => $entity->getEntityTypeId(),
                'entity_id' => $entity->id(),
              ])->toString(),
            ]),
          ],
        ],
        '#status_headings' => ['warning' => t('Warning message')],
        '#weight' => -201,
      ];
      $form["actions"]["submit"]['#attributes']['disabled'] = TRUE;
    }
    else {
      unset($form['entity_usage_delete_warning']);
    }
  }

  protected static function alterTrashMessage(&$form, $entity): void {
    if ($entity->getEntityTypeId() === 'node') {
      unset($form['description']);
      $trash_url = Url::fromRoute('trash.admin_content_trash_entity_type', [
        'entity_type_id' => $entity->getEntityTypeId(),
      ])->toString();

      $form['#attached']['drupalSettings']['mediaFileDelete']['usageDataAvailable'] = FALSE;
      $form['entity_usage_delete_warning'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            t('Once deletion is confirmed, it will be unpublished and moved to Trash. The asset can be purged or restored from the <a href="@trash_url">Trash</a> menu, otherwise it will <a href="@trash_url">automatically be deleted from Trash in 30 days</a>.', [
              '@trash_url' => $trash_url,
            ]),
          ],
        ],
        '#status_headings' => ['warning' => t('Warning message')],
        '#weight' => -201,
      ];
    }
  }

  protected static function countActiveUsages(EntityInterface $entity): int {
    $entityTypeManager = \Drupal::entityTypeManager();
    /** @var \Drupal\entity_usage\EntityUsageInterface $usageService */
    $usageService = \Drupal::service('entity_usage.usage');

    $allUsages = $usageService->listSources($entity, TRUE);

    $activeUsages = 0;
    foreach ($allUsages as $sourceType => $ids) {
      $type_storage = $entityTypeManager->getStorage($sourceType);
      foreach ($ids as $source_id => $records) {
        $sourceEntity = $type_storage->load($source_id);
        if (!$sourceEntity) {
          // If for some reason this record is broken, just skip it.
          continue;
        }
        if ($sourceEntity instanceof RevisionableInterface) {
          $defaultRevisionId = $sourceEntity->getRevisionId();
          $defaultLangcode = $sourceEntity->language()->getId();
          foreach ($records as $key => $record) {
            if (($defaultRevisionId === NULL || $record['source_vid'] == $defaultRevisionId) && $record['source_langcode'] == $defaultLangcode) {
              if (self::rootEntityIsDefault($sourceEntity)) {
                $activeUsages++;
              }
              break;
            }
          }
        }
      }
    }
    return $activeUsages;
  }

  protected static function rootEntityIsDefault(EntityInterface $entity): bool {
    if ($entity->getEntityTypeId() === 'paragraph') {
      if (!$parent = $entity->getParentEntity()) {
        return FALSE;
      }

      // If the reference field on the loaded paragraph is empty then we're
      // not dealing with a default usage.
      $referenceFieldName = $entity->get('parent_field_name')->first()->get('value')->getValue();
      $referenceField = $parent->get($referenceFieldName);
      if ($referenceField->isEmpty()) {
        return FALSE;
      }

      // If the paragraph is nested, recursively apply this function.
      if ($parent->getEntityTypeId() === 'paragraph') {
        return self::rootEntityIsDefault($parent);
      }
    }
    return $entity->isDefaultRevision();
  }

  protected static function isDeleteForm(FormInterface $form_object): bool {
    $config = \Drupal::config('entity_usage.settings');
    $form_classes = $config->get('delete_warning_form_classes') ?: ['Drupal\Core\Entity\ContentEntityDeleteForm'];
    foreach ($form_classes as $class) {
      if ($form_object instanceof $class) {
        return TRUE;
      }
    }
    return FALSE;
  }

}