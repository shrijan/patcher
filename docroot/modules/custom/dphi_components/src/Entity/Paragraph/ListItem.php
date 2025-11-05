<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'list_item',
)]
class ListItem extends Paragraph {

  use FieldValueTrait;

  protected ?EntityInterface $referencedEntity = NULL;

  public function getComponent(): array {
    $this->setReferencedEntity();
    $this->addCacheableDependency($this->referencedEntity);

    $component = [
      'title' => $this->getSingleFieldValue('field_link_text'),
      'type' => $this->getSingleFieldValue('field_links_type'),
      'url' => $this->getUrl(),
      'description' => $this->getDescriptionText(),
      'label' => $this->getLabel(),
      'date' => $this->getPublishDate(),
      'tags' => $this->getTags(),
      'image_position' => $this->getParentEntity()->showImage(),
    ];
    if ($component['image_position'] !== null) {
      $component['image'] = $this->getMediaItem();
    }
    return $component;
  }

  private function setReferencedEntity(): void {
    $linkUri = $this->getSingleFieldValue('field_link');
    if (!$linkUri || UrlHelper::isExternal($linkUri)) {
      return;
    }
    $params = Url::fromUri($linkUri)->getRouteParameters();
    $entity_type = key($params);
    if (!$entity_type) {
      return;
    }
    $entityId = reset($params);
    $this->referencedEntity = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->load($entityId);
  }

  /**
   * Get Description text
   */
  private function getDescriptionText() {
    if (!$this->referencedEntity) {
      return '';
    }
    $descriptionField = '';
    if ($this->referencedEntity->hasField('field_short_description')) {
      if (!$this->referencedEntity->get('field_short_description')->isEmpty()) {
        /** @var \Drupal\text\Plugin\Field\FieldType\TextFieldItemList $descriptionField */
        $descriptionField = $this->referencedEntity->get('field_short_description');
      }
    } else if ($this->referencedEntity->hasField('body')) {
      if (!$this->referencedEntity->get('body')->isEmpty()) {
        /** @var \Drupal\text\Plugin\Field\FieldType\TextFieldItemList $descriptionField */
        $descriptionField = $this->referencedEntity->get('body');
      }
    }
    if (!$descriptionField) {
      return '';
    }

    return $descriptionField->view([
        'label' => 'hidden',
        'type' => 'smart_trim',
        'settings' => [
          'trim_length' => '200',
          'trim_type' => 'chars',
          'trim_suffix' => '...',
        ],
      ]
    );
  }

  private function getLabel(): string {
    if (!$this->referencedEntity) {
      return '';
    }
    if (!$this->getParentEntity()->showLabel()) {
      return '';
    }
    if ($this->referencedEntity->hasField('field_short_description')) {
      if (!$this->referencedEntity->get('field_content_category')
        ->isEmpty()) {
        $content_category = $this->getContentCategories();
        $category = $this->referencedEntity->get('field_content_category')
          ->first()
          ->get('value')
          ->getValue();
        return $content_category[$category];
      }
    }
    else {
      $bundle = $this->referencedEntity->bundle();
      $node_type = \Drupal::entityTypeManager()
        ->getStorage('node_type')
        ->load($bundle);
      if ($node_type) {
        return $node_type->label() ?? '';
      }
    }
    return '';
  }

  /**
   * Get content category from basic page contents.
   */
  private function getContentCategories(): array {
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('node', 'page');
    $content_category = [];
    if (isset($field_definitions['field_content_category'])) {
      $content_category = options_allowed_values($field_definitions['field_content_category']->getFieldStorageDefinition());
    }
    return $content_category;
  }

  private function getPublishDate() {
    if (!$this->referencedEntity) {
      return NULL;
    }
    if (!$this->referencedEntity->hasField('field_publish_event_date')) {
      return NULL;
    }
    $publishDateField = $this->referencedEntity->get('field_publish_event_date');
    if ($publishDateField->isEmpty()) {
      return NULL;
    }
    if (!$this->getParentEntity()->showPublishDate()) {
      return NULL;
    }
    return $publishDateField->first()->get('value')->getValue();
  }

  private function getUrl(): ?GeneratedUrl {
    if ($this->referencedEntity) {
      $url = $this->referencedEntity->toUrl();
    }
    else {
      $linkUri = $this->getSingleFieldValue('field_link');
      if (!$linkUri) {
        return NULL;
      }
      $url = Url::fromUri($linkUri);
    }
    return $url->toString(TRUE);
  }

  private function getMediaItem(): array {
    if ($this->referencedEntity &&
        $this->getParentEntity()->showImage() !== null &&
        $this->referencedEntity->hasField('field_media_image')) {
      return $this->referencedEntity->getImageWithFallback();
    }
    return $this->fallbackImage();
  }

  /**
   * Get Tags
   */
  private function getTags(): array {
    if (!$this->referencedEntity) {
      return [];
    }

    if (!$this->getParentEntity()->showTags()) {
      return [];
    }
    if (!$this->referencedEntity->hasField('field_tags')) {
      return [];
    }

    $tags = $this->referencedEntity->get('field_tags')->referencedEntities();
    $tagLabels = [];
    foreach ($tags as $tag) {
      $tagLabels[] = $tag->getName();
    }
    return $tagLabels;
  }

}
