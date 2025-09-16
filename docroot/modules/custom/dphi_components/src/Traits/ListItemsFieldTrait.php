<?php
namespace Drupal\dphi_components\Traits;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;

trait ListItemsFieldTrait {
  use FieldValueTrait;

  public function showImage(): ?string {
    $fieldValue = $this->getSingleFieldValue('field_show_image');
    if ($fieldValue == 'left') {
      return 'nsw-list-item--reversed';
    } else if ($fieldValue == 'right') {
      return '';
    } else {
      return NULL;
    }
  }

  public function showLabel(): bool {
    return $this->getSingleFieldValue('field_show_label') === '1';
  }

  public function showPublishDate(): bool {
    return $this->getSingleFieldValue('field_show_published_date') === '1';
  }

  public function showTags(): bool {
    return $this->getSingleFieldValue('field_show_tags') === '1';
  }
}
