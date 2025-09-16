<?php

declare(strict_types=1);

namespace Drupal\infobox_buttons\Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

class LimitTableProperties extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;

  const CONFIG_NAME = 'limit_table_properties';

  public function defaultConfiguration(): array {
    return [static::CONFIG_NAME=>true];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form[static::CONFIG_NAME] = [
      '#type' => 'checkbox',
      '#title' => 'Limit table properties',
      '#default_value' => $this->configuration[static::CONFIG_NAME],
      '#description' => 'Remove the Border and Background control rows from "Table Cell Properties" and "Table Properties" popups',
    ];

    return $form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration[static::CONFIG_NAME] = $form_state->getValue(static::CONFIG_NAME);
  }

  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    return [];
  }
}
