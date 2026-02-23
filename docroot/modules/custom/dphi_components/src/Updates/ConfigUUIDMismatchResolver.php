<?php

namespace Drupal\dphi_components\Updates;

class ConfigUUIDMismatchResolver {

  public static function update10201() {
    // Load the config storage directory from settings.php.
    $config_storage = \Drupal::service('config.storage.sync');

    // Get the list of all configuration files in the sync storage.
    $config_names = $config_storage->listAll();

    // Load the active config storage service.
    $active_storage = \Drupal::service('config.storage');

    foreach ($config_names as $config_name) {
      // Load the configuration from the sync storage.
      $sync_config = $config_storage->read($config_name);

      if (!$sync_config) {
        // Skip if config doesn't exist in sync storage.
        continue;
      }

      // Load the corresponding active configuration.
      $active_config = $active_storage->read($config_name);

      if (!$active_config || !array_key_exists('uuid', $active_config)) {
        continue;
      }
      if ($active_config['uuid'] === $sync_config['uuid']) {
        continue;
      }
      // Update the UUID in the active configuration to match the sync config.
      $active_config['uuid'] = $sync_config['uuid'];
      $active_storage->write($config_name, $active_config);
      \Drupal::logger('dphi_components')
        ->notice('Updated UUID for config: @config', ['@config' => $config_name]);
    }

    \Drupal::logger('dphi_components')->notice('Config UUID update completed.');
  }

}
