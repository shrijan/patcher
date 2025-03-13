<?php

declare(strict_types=1);

namespace Drupal\vite;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\vite\Exception\LibraryNotFoundException;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Rewrites libraries to work with vite.
 */
class Vite {

  /**
   * Logger.
   */
  private LoggerInterface $logger;

  /**
   * Constructs the Vite service object.
   */
  public function __construct(
    protected MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    protected ThemeExtensionList $themes,
    protected ModuleExtensionList $modules,
    protected LibraryDiscoveryInterface $libraryDiscovery,
    protected ClientInterface $httpClient,
    protected TranslationInterface $stringTranslation,
    protected string $appRoot,
  ) {
    $this->logger = $logger_factory->get('vite');
  }

  /**
   * Process libraries declared to use vite.
   */
  public function processLibraries(array &$libraries, string $extension): void {
    foreach ($libraries as $libraryId => $library) {
      $assetLibrary = $this->getAssetLibrary($extension, $libraryId, $library);
      if (!$assetLibrary->shouldBeManagedByVite()) {
        continue;
      }
      $libraries[$libraryId] = $this->rewriteLibrary($assetLibrary);
      if ($assetLibrary->shouldUseDevServer()) {
        $this->rewriteDevDependencies($libraries, $assetLibrary);
      }
    }
  }

  /**
   * Rewrite the dev dependencies for the given asset library entry.
   *
   * @param array $libraries
   *   The array of libraries to modify.
   * @param AssetLibrary $assetLibrary
   *   The asset library to retrieve dev dependencies and base URL from.
   */
  private function rewriteDevDependencies(array &$libraries, AssetLibrary $assetLibrary): void {
    foreach ($assetLibrary->getDevDependencies() as $fullDependency) {
      // Split the dependency name on slash to remove the module part.
      $dependencyParts = explode('/', $fullDependency);
      $dependency = end($dependencyParts);
      if (isset($libraries[$dependency])) {
        // Modify the library to add an attribute.
        foreach ($libraries[$dependency]['js'] as $path => $options) {
          $libraries[$dependency]['js'][$path]['attributes']['data-vite-dev-server'] = $assetLibrary->getDevServerBaseUrl();
        }
      }
    }
  }

  /**
   * Get the path to a specific chunk.
   */
  public function getChunk(string $extension, string $libraryId, string $chunk): string {
    $library = $this->libraryDiscovery->getLibraryByName($extension, $libraryId);
    if ($library === FALSE) {
      throw new LibraryNotFoundException("Failed loading library: $extension.$libraryId");
    }

    $assetLibrary = $this->getAssetLibrary($extension, $libraryId, $library);
    if (!$assetLibrary->shouldBeManagedByVite()) {
      return $chunk;
    }

    if ($assetLibrary->shouldUseDevServer()) {
      $devServerBaseUrl = $assetLibrary->getDevServerBaseUrl();
      return $devServerBaseUrl . '/' . ltrim($chunk, '/');
    }

    $manifest = $assetLibrary->getViteManifest();
    return $manifest?->getChunk($chunk) ?? $chunk;
  }

  /**
   * Instantiate AssetLibrary object.
   */
  private function getAssetLibrary(mixed $extension, string $libraryId, mixed $library): AssetLibrary {
    $libraryExtension = $extension;
    $isSdc = $extension === 'core' && str_starts_with($libraryId, 'components.');
    if ($isSdc) {
      $libraryIdWithoutPrefix = str_replace('components.', '', $libraryId);
      $componentExtension = explode('--', $libraryIdWithoutPrefix)[0];
      $libraryExtension = $componentExtension;
    }

    return new AssetLibrary(
      $libraryId,
      $library,
      $libraryExtension,
      $this->messenger,
      $this->logger,
      $this->themes,
      $this->modules,
      $this->httpClient,
      $this->stringTranslation,
      $this->appRoot,
      $isSdc,
    );
  }

  /**
   * Rewrite library for dev or dist.
   */
  private function rewriteLibrary(AssetLibrary $assetLibrary): array {
    if ($assetLibrary->shouldUseDevServer()) {
      return $this->rewriteLibraryForDev($assetLibrary);
    }
    return $this->rewriteLibraryForDist($assetLibrary);
  }

  /**
   * Rewrite library using dist output.
   */
  private function rewriteLibraryForDist(AssetLibrary $assetLibrary): array {
    $manifest = $assetLibrary->getViteManifest();
    $library = $assetLibrary->getDefinition();

    if ($manifest === NULL) {
      return $library;
    }

    if (isset($library['css'])) {
      foreach ($library['css'] as $type => $paths) {
        foreach ($paths as $path => $options) {
          $originalPath = $path;
          if (
            $assetLibrary->isSdc()
            && $this->shouldAssetBeManagedByVite($path, $options)
          ) {
            // Use path beginning from /components/ and remove leading slash.
            $path = ltrim(strstr($path, '/components/'), '/');
          }
          $newPath = $manifest->getChunk($path);
          if ($newPath === NULL) {
            // Don't rewrite assets not present in the manifest.
            continue;
          }
          unset($library['css'][$type][$originalPath]);
          $library['css'][$type][$newPath] = $options;

        }
      }
    }

    if (isset($library['js'])) {
      foreach ($library['js'] as $path => $options) {
        $originalPath = $path;
        if (
          $assetLibrary->isSdc()
          && $this->shouldAssetBeManagedByVite($path, $options)
        ) {
          // Use path beginning from /components/ and remove leading slash.
          $path = ltrim(strstr($path, '/components/'), '/');
        }
        $newPath = $manifest->getChunk($path);
        if ($newPath === NULL) {
          // Don't rewrite assets not present in the manifest.
          continue;
        }
        unset($library['js'][$originalPath]);

        $attributes = $options['attributes'] ?? [];
        $attributes['type'] = 'module';
        $options['attributes'] = $attributes;
        $library['js'][$newPath] = $options;

        $styles = $manifest->getStyles($path);
        foreach ($styles as $stylePath) {
          $library['css']['component'][$stylePath] = [];
        }
      }
    }
    return $library;
  }

  /**
   * Rewrite library to use vite dev server.
   */
  private function rewriteLibraryForDev(AssetLibrary $assetLibrary): array {
    $library = $assetLibrary->getDefinition();

    if ($assetLibrary->getExtension() !== 'vite') {
      $library['dependencies'][] = 'vite/vite-dev-client';
    }

    $devServerBaseUrl = $assetLibrary->getDevServerBaseUrl();
    if (isset($library['css'])) {
      foreach ($library['css'] as $type => $paths) {
        foreach ($paths as $path => $options) {
          if (!$this->shouldAssetBeManagedByVite($path, $options)) {
            continue;
          }
          $originalPath = $path;
          if ($assetLibrary->isSdc()) {
            $path = strstr($path, '/components/');
          }
          unset($library['css'][$type][$originalPath]);
          $options['type'] = 'external';
          $attributes = $options['attributes'] ?? [];
          $attributes['type'] = 'module';
          $options['attributes'] = $attributes;
          $newPath = $devServerBaseUrl . '/' . ltrim($path, '/');
          $library['js'][$newPath] = $options;

        }
      }
    }

    if (isset($library['js'])) {
      foreach ($library['js'] as $path => $options) {
        if (!$this->shouldAssetBeManagedByVite($path, $options)) {
          continue;
        }
        $originalPath = $path;
        if ($assetLibrary->isSdc()) {
          $path = strstr($path, '/components/');
        }
        unset($library['js'][$originalPath]);
        $options['type'] = 'external';
        $attributes = $options['attributes'] ?? [];
        $attributes['type'] = 'module';
        $options['attributes'] = $attributes;
        $newPath = $devServerBaseUrl . '/' . ltrim($path, '/');
        $library['js'][$newPath] = $options;
        if (count($assetLibrary->getDevDependencies()) > 0) {
          $library['dependencies'] = array_merge($library['dependencies'], $assetLibrary->getDevDependencies());
        }
      }

    }
    return $library;
  }

  /**
   * Tries to determine if asset should be managed by vite.
   */
  private function shouldAssetBeManagedByVite(string $path, array $options): bool {
    return $path[0] !== DIRECTORY_SEPARATOR
      && strpos($path, 'http') !== 0
      && (!isset($options['type']) || $options['type'] !== 'external')
      && (!isset($options['vite']) || $options['vite'] !== FALSE)
      && (!isset($options['vite']['enabled']) || $options['vite']['enabled'] !== FALSE);
  }

}
