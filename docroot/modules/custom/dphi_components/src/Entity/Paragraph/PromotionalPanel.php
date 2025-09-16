<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'promotional_panel',
)]
class PromotionalPanel extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $desktop_image = $this->get('field_image_upload')->entity;
    $mobile_image = $this->get('field_image_upload_mobile')->entity;
    return [
      'heading' => $this->getSingleFieldValue('field_title'),
      'description' => $this->getContentFieldValue('field_description'),
      'backgroundClass' => $this->getBackgroundClass(),
      'parallaxClass' => $this->getParallaxScrolling(),
      'desktopImage' => $this->getImage('field_image_upload'),
      'mobileImage' => $this->getImage('field_image_upload_mobile'),
      'camera_icon' => $this->getBooleanValue('field_show_camera_icon'),
      'image_credit' => $desktop_image ? $desktop_image->getSingleFieldValue('field_image_credit') : '',
      'image_description' => $desktop_image ? $desktop_image->getSingleFieldValue('field_image_description') : '',
      'mobile_image_credit' => $mobile_image ? $mobile_image->getSingleFieldValue('field_image_credit') : '',
      'mobile_image_description' => $mobile_image ? $mobile_image->getSingleFieldValue('field_image_description') : '',
      'transparentOption' => $this->getSingleFieldValue('field_transparent'),
      'link' => $this->getUrl(),
    ];
  }

  private function getBackgroundClass(): string {
    $backgroundColor = $this->getSingleFieldValue('field_background_colour');

    if ($backgroundColor == "Brand Dark") {
      return "brand-dark";
    }
    if ($backgroundColor == "Brand Light") {
      return "brand-light";
    }
    if ($backgroundColor == "Brand Supplementary") {
      return "brand-supplementary";
    }
    if ($backgroundColor == "Brand Accent") {
      return "brand-accent";
    }
    if ($backgroundColor == "White") {
      return "brand-white";
    }

    return '';
  }

  private function getButtonClass(): string {
    $backgroundColor = $this->getSingleFieldValue('field_background_colour');

    if (in_array($backgroundColor, ["Brand Light", "White"])) {
      return "nsw-button--dark";
    }

    return 'nsw-button--white';
  }

  private function getParallaxScrolling(): string {
    $parallaxValue = $this->getSingleFieldValue('field_enable_parallax_scrolling');

    return $parallaxValue == 1 ? 'parallax' : '';
  }

  private function getImage(string $fieldName): string {
    $mediaItem = $this->getFirstReferencedEntity($fieldName);

    if ($mediaItem && $mediaItem->getEntityTypeId() === 'media') {
      /** @var \Drupal\file\FileInterface $imageFileEntity */
      $imageFileEntity = $this->getFirstReferencedEntity('field_media_image', $mediaItem);

      if ($imageFileEntity) {
        return $imageFileEntity->createFileUrl();
      }
    }

    return '';
  }

  private function getUrl(): array {
    /** @var \Drupal\link\LinkItemInterface $urlItem */
    $urlItem = $this->get('field_url')->first();

    if ($urlItem) {
      // Retrieve URL and link text
      $url = $urlItem->getUrl();

      return [
        'url' => $url->toString(),
        'text' => $urlItem->getTitle(),
        'classes' => 'nsw-button ' . $this->getButtonClass(),
        'icon' => 'east',
      ];
    }

    return [];
  }

}
