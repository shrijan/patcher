<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'form_assembly_collaboration_form',
)]
class FormAssembly extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  public function getComponent(): array {
    $component = [];
    foreach (['head', 'body'] as $key) {
      $value = $this->getContentFieldValue('field_'.$key.'_html');
      if ($value) {
        $component[$key] = $value['#items'][0]->get('value')->getValue();
      }
    }
    return $component;
  }

}
