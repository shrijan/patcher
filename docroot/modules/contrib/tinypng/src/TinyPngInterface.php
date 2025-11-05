<?php

namespace Drupal\tinypng;

/**
 * TinyPng connector service Interface.
 *
 * @package Drupal\tinypng
 */
interface TinyPngInterface {

  /**
   * Set TinyPNG API key.
   *
   * @param string $key
   *   TinyPNG API key.
   * @param bool $reset
   *   Force to update previous API key.
   *
   * @return $this
   *   Current instance.
   */
  public function setApiKey($key = NULL, $reset = FALSE);

  /**
   * Compress with \Tinify\fromUrl.
   *
   * @param string $url
   *   URI of image file.
   *
   * @return $this
   *   Current instance.
   */
  public function setFromUrl($url);

  /**
   * Compress with \Tinify\fromFile.
   *
   * @param string $uri
   *   URI of file.
   *
   * @return $this
   *   Current instance.
   */
  public function setFromFile($uri);

  /**
   * Save result to file.
   *
   * @param string $uri
   *   Destination URI.
   *
   * @return bool|int
   *   Size of new file.
   */
  public function saveTo($uri);

}
