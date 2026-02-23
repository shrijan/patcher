<?php
namespace Drupal\dphi_components\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;

trait FieldValueTrait {

  public function getBooleanValue(string $field_name) {
    return $this->get($field_name)->value;
  }
  public function getSingleFieldValue(string $field_name): string {
    if (!$this->hasField($field_name)) {
      return '';
    }
    if ($this->get($field_name)->isEmpty()) {
      return '';
    }
    return $this->get($field_name)->getString();
  }

  public function getContentFieldValue(string $field_name): array {
    if (!$this->get($field_name)->isEmpty()) {
      return $this->get($field_name)->view([
        'label' => 'hidden',
        'type' => 'text_default',
      ]);
    }
    return [];
  }

  public function getImageWithFallback(): array {
    if ($this->hasField('field_media_image') && $this->get('field_media_image')->entity) {
      return $this->get('field_media_image')->view('media');
    }
    return $this->fallbackImage();
  }

  public function fallbackImage(): array {
    $config = \Drupal::config('dphi_components.settings');
    $src = $config->get('fallback_image_path') ?: '/modules/custom/dphi_components/themes/dphi_base_theme/images/fallback-image.jpg';
    $altText = $config->get('fallback_image_alt_text') ?: 'NSW Government logo';
    return [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => $src,
        'width' => 400,
        'height' => 252,
        'alt' => $altText,
      ],
    ];
  }

  public function getFirstReferencedEntity(string $field_name, EntityInterface $entity = NULL): ?EntityInterface {
    $entity = $entity ?: $this;
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
    $field = $entity->get($field_name);
    return $field->referencedEntities()[0] ?? NULL;
  }

  private function getIcon(string $field_name): string {
    if ($this->hasField($field_name) && !$this->get($field_name)->isEmpty()) {
      return $this->get($field_name)->first()->get('icon')->getString();
    }
    return '';
  }

  private function isIconOutlined(string $field_name): bool {
    if ($this->hasField($field_name) && !$this->get($field_name)->isEmpty()) {
      return $this->get($field_name)->first()->get('family')->getValue() == 'symbols__outlined';
    }
    return false;
  }

  private function getIconClass(string $field_name): string {
    if ($this->hasField($field_name) && !$this->get($field_name)->isEmpty()) {
      return $this->get($field_name)->first()->get('classes')->getString();
    }
    return '';
  }

  /**
   * Get focal point processing for a media image.
   */
  private function getFocalPointCoordinates(FileInterface $image): ?array {

    // Services.
    $focal_point_manager = \Drupal::service('focal_point.manager');
    $file_system = \Drupal::service('file_system');

    // Get the crop entity.
    $crop = $focal_point_manager->getCropEntity($image, 'focal_point');

    if (!$crop) {
      return [];
    }

    // Get the x and y position from the crop.
    $fp_abs = $crop->position();

    // Get the image size.
    if (!$filePath = $file_system->realpath($image->getFileUri())) {
      return [];
    }
    if (!is_file($filePath)) {
      return [];
    }
    $image_info = getimagesize($filePath) ?? NULL;

    if (!$image_info) {
      return [];
    }

    // Convert absolute to relative focal point coordinates.
    $fp_rel = $focal_point_manager->absoluteToRelative($fp_abs['x'], $fp_abs['y'], $image_info[0], $image_info[1]);

    // Return focal point coordinates in percentage.
    return [
      'width' => $image_info[0],
      'height' => $image_info[1],
      'focal_x' => $fp_rel['x'] . '%',
      'focal_y' => $fp_rel['y'] . '%',
    ];
  }

  private function getHeroImage(string $field_name): array {
    $component = [];
    $media = $this->get($field_name)->referencedEntities();
    if ($media) {
      $media = reset($media);
      $file_url_generator = \Drupal::service('file_url_generator');
      if ($fileUri = $media->field_media_image->entity->getFileUri()) {
        $component['image_url'] = $file_url_generator->generate($fileUri);
      }
      $component['image_credit'] = $media->getSingleFieldValue('field_image_credit');
      $component['image_description'] = $media->getSingleFieldValue('field_image_description');
      $component['alt_text'] = $media->thumbnail->alt;
      $image = $media->get('field_media_image')->referencedEntities();
      if ($image) {
        $image = reset($image);
        $component = array_merge($component, $this->getFocalPointCoordinates($image));
      }
    }
    return $component;
  }
}
