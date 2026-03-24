<?php

namespace Drupal\material_icons\Traits;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Delivers basic module settings and constants.
 */
trait MaterialIconsSettings {

  use StringTranslationTrait;

  /**
   * Delivers an array of libraries being supported by the module.
   *
   * @return array
   *   An array of font sets.
   */
  public function fontSets() {
    return [
      'material-icons' => [
        'name' => $this->t('Material Icons'),
        'meta_url' => 'https://fonts.google.com/metadata/icons',
        'prefix' => '',
        'cache_string' => 'materialicons.icons',
        'font_families' => [
          'baseline' => $this->t('Material Icons - Filled'),
          'outlined' => $this->t('Material Icons - Outlined'),
          'round' => $this->t('Material Icons - Rounded'),
          'sharp' => $this->t('Material Icons - Sharp'),
          'two-tone' => $this->t('Material Icons - Two-Tone'),
        ],
      ],
      'material-symbols' => [
        'name' => $this->t('Material Symbols'),
        'meta_url' => 'https://fonts.google.com/metadata/icons?key=material_symbols&incomplete=true',
        'prefix' => 'symbols__',
        'cache_string' => 'materialsymbols.icons',
        'font_families' => [
          'symbols__outlined' => $this->t('Material Symbols - Outlined'),
          'symbols__rounded' => $this->t('Material Symbols - Rounded'),
          'symbols__sharp' => $this->t('Material Symbols - Sharp'),
        ],
      ],
    ];
  }

  /**
   * Delivers the key of the default font set.
   *
   * @return string
   *   The string that uniquely identifies the default font set.
   */
  public function getDefaultFontSet() {
    return 'material-icons';
  }

  /**
   * Delivers a key/value list of font families to be picked from.
   *
   * @return array
   *   An array of font families.
   */
  public function getFontFamilies() {
    $font_sets = $this->fontSets();
    return array_reduce($font_sets, fn ($carry, $font_set) => array_merge($carry, $font_set['font_families']), []);
  }

  /**
   * Delivers the corresponding endpoint for any of the supported libraries.
   *
   * @param string $font_set
   *   The key that uniquely identifies a given font set.
   *
   * @return string|Null
   *   The URL that points to the metadata for the font set.
   */
  public function getFontSetMetaUrl($font_set) {
    return $this->fontSets()[$font_set]['meta_url'] ?? NULL;
  }

  /**
   * Delivers the prefix associated with the current font set.
   *
   * @param string $font_set
   *   The key that uniquely identifies a given font set.
   *
   * @return mixed|null
   *   The prefix that is used to identify the font set.
   */
  public function getFontSetPrefix($font_set) {
    return $this->fontSets()[$font_set]['prefix'] ?? NULL;
  }

  /**
   * Delivers the cache string associated with the current font set.
   *
   * @param string $font_set
   *   The key that uniquely identifies a given font set.
   *
   * @return string
   *   The string that identifies the list of icons in the active font set.
   */
  public function getCacheString($font_set) {
    return $this->fontSets()[$font_set]['cache_string'] ?? NULL;
  }

  /**
   * Gets the font set associated with the provided font family.
   *
   * @param string $font_family
   *   The font family to be checked against.
   *
   * @return mixed|null
   *   The key with the font family.
   */
  public function getFontSetFromFamily($font_family): ?string {
    $matchingBaseKeys = array_keys(array_filter($this->fontSets(), fn ($font_group) => array_key_exists($font_family, $font_group['font_families'])));

    return !empty($matchingBaseKeys) ? reset($matchingBaseKeys) : NULL;
  }

  /**
   * Gets an array of font families associated with the given font set.
   *
   * @param string $font_set
   *   The key that uniquely identifies a given font set.
   *
   * @return array|null
   *   An array of font families associated with the font set.
   */
  public function getFontSetFamilies($font_set): ?array {
    return $this->fontSets()[$font_set]['font_families'];
  }

  /**
   * Processes a string to return the CSS class name of the font family.
   *
   * @param string $font_family
   *   The font family to be processed.
   *
   * @return string
   *   The CSS class name of the font family.
   */
  public function getFontFamilyClass($font_family): string {
    $font_set = $this->getFontSetFromFamily($font_family);
    $prefix = $this->getFontSetPrefix($font_set);
    $font_family_type = str_starts_with($font_family, $prefix) ? substr($font_family, strlen($prefix)) : $font_family;
    return $font_family === 'baseline' ? $font_set : "$font_set-$font_family_type";
  }

}
