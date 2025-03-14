<?php

namespace Drupal\scanner\Plugin\Scanner;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A Scanner plugin for handling Paragraph entities.
 *
 * @Scanner(
 *   id = "scanner_paragraph",
 *   type = "paragraph",
 * )
 */
class Paragraph extends Entity {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function search($field, array $values) {
    $title_collect = [];
    [, $bundle, $fieldname] = explode(':', $field);

    $query = $this->entityTypeManager->getStorage('paragraph')->getQuery();
    $query->condition('type', $bundle);
    if ($values['published']) {
      $query->condition('status', 1);
    }
    $conditionVals = parent::buildCondition($values['search'], $values['mode'], $values['wholeword'], $values['regex'], $values['preceded'], $values['followed']);
    $this->addQueryCondition($query, $conditionVals, $fieldname, $values['mode'], $values['language']);

    // Disable the normal access check.
    $query->accessCheck(FALSE);

    $entities = $query->execute();

    if (!empty($entities)) {
      // Load the paragraph(s) which match the criteria.
      $paragraphs = $this->entityTypeManager->getStorage('paragraph')->loadMultiple($entities);
      // Iterate over matched paragraphs to extract information that will be
      // rendered in the results.
      foreach ($paragraphs as $paragraph) {
        if (!empty($paragraph)) {
          // Load the entity the paragraph is referenced in.
          $parentEntity = $paragraph->getParentEntity();

          if (!empty($parentEntity)) {
            $parentEntityType = $parentEntity->getEntityTypeId();
            // In the case of nested relationships we need to find the base
            // entity.
            if ($parentEntityType != 'node') {
              // If child is only nested one level deep.
              if ($parentEntity->getParentEntity()->getEntityTypeId() == 'node') {
                $parentEntity = $parentEntity->getParentEntity();
              }
              // Two or more levels of nesting.
              else {
                while ($parentEntity->getParentEntity()->getEntityTypeId() != 'node') {
                  $parentEntity = $parentEntity->getParentEntity();
                }
              }
            }
            $id = $parentEntity->id();
            // Get the value of the specified field.
            $paraField = $paragraph->get($fieldname);
            $fieldType = $paraField->getFieldDefinition()->getType();
            if (in_array($fieldType, ['text_with_summary', 'text', 'text_long'])) {
              // Get the value of the field.
              $fieldValue = $paraField->getValue()[0];
              // Get the parent entity's title.
              $title_collect[$id]['title'] = $parentEntity->getTitle();
              // Find all instances of the term we're looking for.
              preg_match_all($conditionVals['phpRegex'], $fieldValue['value'], $matches, PREG_OFFSET_CAPTURE);
              $newValues = [];
              // Build an array of strings which are displayed in the results
              // with the searched term bolded.
              foreach ($matches[0] as $v) {
                // The offset of the matched term(s) in the field's text.
                $start = $v[1];
                if ($values['preceded'] !== '') {
                  // Bolding won't work if starting position is in the middle
                  // of a word (non-word bounded searches), therefore we move
                  // the start position back as many character as there are in
                  // the 'preceded' text.
                  $start -= strlen($values['preceded']);
                }
                $replaced = preg_replace($conditionVals['phpRegex'], "<strong>$v[0]</strong>", preg_split("/\s+/", substr($fieldValue['value'], $start), 6));
                if (count($replaced) > 1) {
                  // The final index contains the remainder of the text, which
                  // we don't care about so we discard it.
                  array_pop($replaced);
                }
                $newValues[] = implode(' ', $replaced);
              }
              $title_collect[$id]['field'] = $newValues;
            }
            elseif ($fieldType == 'string') {
              $title_collect[$id]['title'] = $parentEntity->getTitle();
              preg_match($conditionVals['phpRegex'], $paraField->getString(), $matches, PREG_OFFSET_CAPTURE);
              $match = $matches[0][0];
              $replaced = preg_replace($conditionVals['phpRegex'], "<strong>$match</strong>", $paraField->getString());
              $title_collect[$id]['field'] = [$replaced];
            }
          }
        }
      }
    }
    return $title_collect;
  }

  /**
   * {@inheritdoc}
   */
  public function replace($field, array $values, array $undo_data) {
    [$entityType, $bundle, $fieldname] = explode(':', $field);
    $data = $undo_data;

    $query = $this->entityTypeManager->getStorage($entityType)->getQuery();
    $query->condition('type', $bundle);
    if ($values['published']) {
      $query->condition('status', 1);
    }
    $conditionVals = parent::buildCondition($values['search'], $values['mode'], $values['wholeword'], $values['regex'], $values['preceded'], $values['followed']);
    $this->addQueryCondition($query, $conditionVals, $fieldname, $values['mode'], $values['language']);

    // Disable the normal access check.
    $query->accessCheck(FALSE);

    $entities = $query->execute();

    $paragraphs = $this->entityTypeManager->getStorage('paragraph')->loadMultiple($entities);
    foreach ($paragraphs as $pid => $paragraph) {
      $paraField = $paragraph->get($fieldname);
      $fieldType = $paraField->getFieldDefinition()->getType();
      if (in_array($fieldType, ['text_with_summary', 'text', 'text_long'])) {
        $fieldValue = $paraField->getValue()[0];
        $fieldValue['value'] = preg_replace($conditionVals['phpRegex'], $values['replace'], $fieldValue['value']);
        $paragraph->{$fieldname} = $fieldValue;
        if (!isset($data["paragraph:$pid"]['new_vid'])) {
          $data["paragraph:$pid"]['old_vid'] = $paragraph->getRevisionId();
          // Create a new revision for the paragraph.
          $paragraph->setNewRevision(TRUE);
        }
        // Save the paragraph with the updated field(s).
        $paragraph->save();
        $data["paragraph:$pid"]['new_vid'] = $paragraph->getRevisionId();
        $processed = $this->handleParentRelationship($paragraph, $values, $data);
        if (!$processed) {
          // We couldn't handle the relationship for some reason so we move on
          // to the next paragraph.
          continue;
        }
      }
      elseif ($fieldType == 'string') {
        $fieldValue = preg_replace($conditionVals['phpRegex'], $values['replace'], $paraField->getString());
        $paragraph->$fieldname = $fieldValue;
        if (!isset($data["paragraph:$pid"]['new_vid'])) {
          $data["paragraph:$pid"]['old_vid'] = $paragraph->getRevisionId();
          $paragraph->setNewRevision(TRUE);
        }
        $paragraph->save();
        $data["paragraph:$pid"]['new_vid'] = $paragraph->getRevisionId();
        $processed = $this->handleParentRelationship($paragraph, $values, $data);
        if (!$processed) {
          // We couldn't handle the relationship for some reason so we move on
          // to the next paragraph.
          continue;
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function undo(array $data) {
    // Load the specified paragraph revision.
    $paraRevision = $this->entityTypeManager->getStorage('paragraph')->loadRevision($data['old_vid']);
    $paraRevision->setNewRevision(TRUE);
    // Set this revision as the current/default revision.
    $paraRevision->isDefaultRevision(TRUE);
    $paraRevision->save();
  }

  /**
   * Helper function to handle entity reference relationships.
   *
   * @param mixed $paragraph
   *   The paragraph entity.
   * @param array $values
   *   An array containing the input values from the form.
   * @param array $data
   *   An array containing the revision id's for the entities being processed.
   *
   * @return bool
   *   A boolean which denotes whether we were able to process the parent
   *   entity(s).
   */
  protected function handleParentRelationship($paragraph, array $values, array &$data) {
    $pid = $paragraph->id();
    $parentEntity = $paragraph->getParentEntity();
    if (empty($parentEntity)) {
      return FALSE;
    }
    $id = $parentEntity->id();
    $parentEntityType = $parentEntity->getEntityTypeId();
    $isProcessed = FALSE;

    if ($parentEntityType == 'node') {
      $parentField = $paragraph->get('parent_field_name')->getString();
      $index = $this->getMultiValueIndex($parentEntity->$parentField->getValue(), $pid);
      // Orphaned paragraphs cause issues so we skip them (and their
      // relationships).
      if ($index < 0) {
        $this->getLogger('scanner')->notice('Unable to find the delta for this paragraph in the parent entity\'s field (id: @id).', ['@id' => $pid]);
        return $isProcessed;
      }
      if (!isset($data["node:$id"]['new_vid'])) {
        $data["node:$id"]['old_vid'] = $parentEntity->getRevisionId();
        // Create a new revision for the parent entity.
        $parentEntity->setNewRevision(TRUE);
        $parentEntity->revision_log = $this->t('Replaced @search with @replace via Scanner Search and Replace module.', [
          '@search' => $values['search'],
          '@replace' => $values['replace'],
        ]);
      }
      // We need to update the parent entity as well so that it will display
      // the lastest revision.
      $parentEntity->$parentField->set($index, [
        'target_id' => $pid,
        'target_revision_id' => $paragraph->getRevisionId(),
      ]);
      $parentEntity->save();
      $data["node:$id"]['new_vid'] = $parentEntity->getRevisionId();
      $isProcessed = TRUE;
      return $isProcessed;
    }
    elseif ($parentEntityType == 'paragraph') {
      $grandParentEntity = $parentEntity->getParentEntity();
      // Bail out if grandparent isn't a node, we only handle two levels of
      // nesting.
      if ($grandParentEntity->getEntityTypeId() != 'node') {
        return $isProcessed;
      }
      $parentField = $paragraph->get('parent_field_name')->getString();
      $index = $this->getMultiValueIndex($parentEntity->$parentField->getValue(), $pid);
      // Orphaned paragraphs cause issues so we skip them (and their
      // relationships).
      if ($index < 0) {
        $this->getLogger('scanner')->notice('Unable to find the delta for this paragraph in the parent entity\'s field (id: @id).', ['@id' => $pid]);
        return $isProcessed;
      }
      // Handle parent entity.
      if (!isset($data["paragraph:$id"]['new_vid'])) {
        $data["paragraph:$id"]['old_vid'] = $parentEntity->getRevisionId();
        // Create a new revision for the paragraph.
        $parentEntity->setNewRevision(TRUE);
      }
      $parentEntity->$parentField->set($index, [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ]);
      $parentEntity->save();
      $data["paragraph:$id"]['new_vid'] = $parentEntity->getRevisionId();

      // Handle grandparent entity.
      $grandParentId = $grandParentEntity->id();
      $grandParentField = $parentEntity->get('parent_field_name')->getString();
      $index = $this->getMultiValueIndex($grandParentEntity->$grandParentField->getValue(), $id);
      // Orphaned paragraphs can cause issues so we skip them.
      if ($index < 0) {
        $this->getLogger('scanner')->notice('Unable to find the delta for this paragraph in the parent entity\'s field (id: @id).', ['@id' => $id]);
        return FALSE;
      }
      if (!isset($data["node:$grandParentId"]['new_vid'])) {
        $data["node:$grandParentId"]['old_vid'] = $grandParentEntity->getRevisionId();
        // Create a new revision for the paragraph.
        $grandParentEntity->setNewRevision(TRUE);
        $grandParentEntity->revision_log = $this->t('Replaced @search with @replace via Scanner Search and Replace module.', [
          '@search' => $values['search'],
          '@replace' => $values['replace'],
        ]);
      }
      $grandParentEntity->$grandParentField->set($index, [
        'target_id' => $parentEntity->id(),
        'target_revision_id' => $parentEntity->getRevisionId(),
      ]);
      $grandParentEntity->save();
      $data["node:$grandParentId"]['new_vid'] = $grandParentEntity->getRevisionId();
      return TRUE;
    }
    else {
      // Something we didn't expect.
      return FALSE;
    }
  }

  /**
   * Get multiple value index.
   */
  protected function getMultiValueIndex($values, $pid) {
    foreach ($values as $key => $value) {
      if ($value['target_id'] == $pid) {
        return $key;
      }
    }
    return -1;
  }

}
