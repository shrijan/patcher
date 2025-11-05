<?php

namespace Drupal\dphi_components\Traits;

use Drupal\Core\Field\FieldItemListInterface;

trait PaddingControlTrait {
  /**
   * Get the padding control value from the field.
   *
   * @return string
   *   The padding control value.
   */
  public function getPaddingControlValue() {
    if (!$this->hasField('field_padding_control')) {
      return 'standard';
    }

    $padding_control_field = $this->get('field_padding_control');
    if ($padding_control_field instanceof FieldItemListInterface && !$padding_control_field->isEmpty()) {
      return $padding_control_field->getString();
    }

    return 'condensed';
  }

  /**
   * Get the padding control class based on the field value.
   *
   * @return string
   *   The padding control class.
   */
  public function getPaddingControlClass() {
    $padding_control_value = $this->getPaddingControlValue();
    switch ($padding_control_value) {
      case 'condensed':
        return 'nsw-section--no-padding';
      case 'standard':
        return 'nsw-section--half-padding';
      case 'extra':
      default:
        return '';
    }
  }
}
