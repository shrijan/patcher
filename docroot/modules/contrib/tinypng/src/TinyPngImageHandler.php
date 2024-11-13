<?php

namespace Drupal\tinypng;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TinyPng image handler service.
 *
 * @package Drupal\tinypng
 */
class TinyPngImageHandler implements TinyPngImageHandlerInterface, ContainerInjectionInterface {

  /**
   * List of supported mime types.
   *
   * @var string[]
   */
  protected $supportedMimeTypes = [
    'image/png',
    'image/jpg',
    'image/jpeg',
  ];

  /**
   * Settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * TinyPng service.
   *
   * @var \Drupal\tinypng\TinyPngInterface
   */
  protected $tinyPng;

  /**
   * Image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tinypng.compress'),
      $container->get('config.factory'),
      $container->get('image.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * TinyPngImageHandler constructor.
   *
   * @param \Drupal\tinypng\TinyPngInterface $tiny_png
   *   TinyPng service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   Image factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   Logger channel factory.
   */
  public function __construct(
    TinyPngInterface $tiny_png,
    ConfigFactoryInterface $config_factory,
    ImageFactory $image_factory,
    LoggerChannelFactoryInterface $logger_channel_factory
  ) {
    $this->tinyPng = $tiny_png;
    $this->config = $config_factory->get('tinypng.settings');
    $this->imageFactory = $image_factory;
    $this->logger = $logger_channel_factory->get('tinypng');
  }

  /**
   * {@inheritdoc}
   */
  public function hookEntityPresave(EntityInterface $entity) {
    // Handle only newly uploaded images.
    if (!$this->shouldHandleEntity($entity)) {
      return;
    }

    /** @var \Drupal\file\FileInterface $entity */
    // Compress image.
    $image_path = $entity->getFileUri();
    try {
      if ($this->config->get('upload_method') === 'download') {
        $this->tinyPng->setFromUrl($image_path);
      }
      else {
        $this->tinyPng->setFromFile($image_path);
      }

      $this->tinyPng->saveTo($image_path);
    }
    catch (\Exception $ex) {
      $this->logger->error($ex->getMessage());
    }
  }

  /**
   * Checks if we should handle entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check.
   *
   * @return bool
   *   Returns TRUE if module configured and given entity is image.
   */
  protected function shouldHandleEntity(EntityInterface $entity) {
    if (!$this->checkConfig()) {
      return FALSE;
    }

    if (!$this->checkEntity($entity)) {
      return FALSE;
    }

    /** @var \Drupal\file\FileInterface $entity */
    if (!$this->checkFile($entity)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if given entity is a new File entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check.
   *
   * @return bool
   *   Returns TRUE if given entity is a new File.
   */
  protected function checkEntity(EntityInterface $entity) {
    return (
      $entity->isNew()
      && $entity->getEntityTypeId() === 'file'
    );
  }

  /**
   * Check if given file entity is an image with a supported type.
   *
   * @param \Drupal\file\FileInterface $file
   *   File entity to check.
   *
   * @return bool
   *   Returns TRUE if given file is an image with supported type.
   */
  protected function checkFile(FileInterface $file) {
    return (
      $this->isImage($file)
      && $this->isMimeSupported($file->getMimeType())
    );
  }

  /**
   * Check if module configured.
   *
   * @return bool
   *   Returns TRUE if API key upload method is configured.
   */
  protected function checkConfig() {
    return (
      !empty($this->config->get('api_key'))
      && !empty($this->config->get('on_upload'))
    );
  }

  /**
   * Check if given entity is a valid image.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check.
   *
   * @return bool
   *   Returns if image factory validation returns TRUE.
   *
   * @see \Drupal\Core\Image\ImageInterface::isValid()
   */
  protected function isImage(EntityInterface $entity) {
    $image = $this->imageFactory->get($entity->getFileUri());
    return $image->isValid();
  }

  /**
   * Check if given mime is supported.
   *
   * @param string $mime
   *   Mime type to check.
   *
   * @return bool
   *   Returns TRUE if given mime type string is supported.
   */
  protected function isMimeSupported($mime) {
    return in_array($mime, $this->supportedMimeTypes);
  }

}
