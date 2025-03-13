<?php

namespace Drupal\tinypng\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image\Controller\ImageStyleDownloadController;
use Drupal\image\ImageStyleInterface;
use Drupal\tinypng\TinyPngInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * TinyPng image style download controller.
 *
 * @package Drupal\tinypng\Controller
 */
class TinyPngImageStyleDownloadController extends ImageStyleDownloadController {

  /**
   * TinyPng compress service.
   *
   * @var \Drupal\tinypng\TinyPngInterface
   */
  protected $tinyPng;

  /**
   * TinyPng logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $serviceLogger;

  /**
   * {@inheritdoc}
   *
   * This is not a good solution. Replace this if
   * https://www.drupal.org/project/drupal/issues/2940016 is closed.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lock'),
      $container->get('image.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('file_system'),
      $container->get('logger.factory'),
      $container->get('tinypng.compress')
    );
  }

  /**
   * {@inheritdoc}
   *
   * This is not a good solution. Replace this if
   * https://www.drupal.org/project/drupal/issues/2940016 is closed.
   */
  public function __construct(
    LockBackendInterface $lock,
    ImageFactory $image_factory,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    FileSystemInterface $fs,
    LoggerChannelFactoryInterface $logger_factory,
    TinyPngInterface $tiny_png,
  ) {
    parent::__construct(
      $lock,
      $image_factory,
      $stream_wrapper_manager,
      $fs
    );
    $this->serviceLogger = $logger_factory->get('tinypng');
    $this->tinyPng = $tiny_png;
  }

  /**
   * {@inheritdoc}
   *
   * This is not a good solution. Replace this if
   * https://www.drupal.org/project/drupal/issues/2940016 is closed.
   */
  public function deliver(
    Request $request,
    $scheme,
    ImageStyleInterface $image_style,
    string $required_derivative_scheme = 'public',
  ) {
    // If compression is not enabled for image style use core image deliver
    // method.
    if (!$image_style->getThirdPartySetting('tinypng', 'tinypng_compress')) {
      return parent::deliver($request, $scheme, $image_style, $required_derivative_scheme);
    }

    $target = $request->query->get('file');
    $image_uri = $scheme . '://' . $target;
    $image_uri = $this->streamWrapperManager->normalizeUri($image_uri);
    $sample_image_uri = $scheme . '://' . $this->config('image.settings')->get('preview_image');

    if ($this->streamWrapperManager->isValidScheme($scheme)) {
      $normalized_target = $this->streamWrapperManager->getTarget($image_uri);
      if ($normalized_target !== FALSE) {
        if (!in_array($scheme, Settings::get('file_sa_core_2023_005_schemes', []))) {
          $parts = explode('/', $normalized_target);
          if (array_intersect($parts, ['.', '..'])) {
            throw new NotFoundHttpException();
          }
        }
      }
    }

    // Check that the style is defined and the scheme is valid.
    $valid = !empty($image_style) && $this->streamWrapperManager->isValidScheme($scheme);

    // Also validate the derivative token. Sites which require image
    // derivatives to be generated without a token can set the
    // 'image.settings:allow_insecure_derivatives' configuration to TRUE to
    // bypass this check, but this will increase the site's vulnerability
    // to denial-of-service attacks. To prevent this variable from leaving the
    // site vulnerable to the most serious attacks, a token is always required
    // when a derivative of a style is requested.
    // The $target variable for a derivative of a style has
    // styles/<style_name>/... as structure, so we check if the $target variable
    // starts with styles/.
    $token = $request->query->get(IMAGE_DERIVATIVE_TOKEN, '');
    $token_is_valid = hash_equals($image_style->getPathToken($image_uri), $token)
      || hash_equals($image_style->getPathToken($scheme . '://' . $target), $token);
    if (
      !$this->config('image.settings')->get('allow_insecure_derivatives')
      || str_starts_with(ltrim($target, '\/'), 'styles/')
    ) {
      $valid = $valid && $token_is_valid;
    }

    if (!$valid) {
      // Return a 404 (Page Not Found) rather than a 403 (Access Denied) as the
      // image token is for DDoS protection rather than access checking. 404s
      // are more likely to be cached (e.g. at a proxy) which enhances
      // protection from DDoS.
      throw new NotFoundHttpException();
    }

    $derivative_uri = $image_style->buildUri($image_uri);
    $derivative_scheme = $this->streamWrapperManager->getScheme($derivative_uri);

    if ($required_derivative_scheme !== $derivative_scheme) {
      throw new AccessDeniedHttpException("The scheme for this image doesn't match the scheme for the original image");
    }

    if ($token_is_valid) {
      $is_public = ($scheme !== 'private');
    }
    else {
      $core_schemes = ['public', 'private', 'temporary'];
      $additional_public_schemes = array_diff(Settings::get('file_additional_public_schemes', []), $core_schemes);
      $public_schemes = array_merge(['public'], $additional_public_schemes);
      $is_public = in_array($derivative_scheme, $public_schemes, TRUE);
    }

    $headers = [];

    // Don't try to generate file if source is missing.
    if ($image_uri !== $sample_image_uri && !$this->sourceImageExists($image_uri, $token_is_valid)) {
      // If the image style converted the extension, it has been added to the
      // original file, resulting in filenames like image.png.jpeg. So to find
      // the actual source image, we remove the extension and check if that
      // image exists.
      $converted_image_uri = static::getUriWithoutConvertedExtension($image_uri);
      if ($converted_image_uri !== $image_uri &&
          $this->sourceImageExists($converted_image_uri, $token_is_valid)) {
        // The converted file does exist, use it as the source.
        $image_uri = $converted_image_uri;
      }
      else {
        $this->logger->notice(
          'Source image at %source_image_path not found while trying to generate derivative image at %derivative_path.',
          [
            '%source_image_path' => $image_uri,
            '%derivative_path' => $derivative_uri,
          ]
        );
        return new Response($this->t('Error generating image, missing source file.'), 404);
      }
    }

    // If not using a public scheme, let other modules provide headers and
    // control access to the file.
    if (!$is_public) {
      $headers = $this->moduleHandler()->invokeAll('file_download', [$image_uri]);
      if (in_array(-1, $headers) || empty($headers)) {
        throw new AccessDeniedHttpException();
      }
    }

    // If it is default sample.png, ignore scheme.
    // This value swap must be done after hook_file_download is called since
    // the hooks are expecting a URI, not a file path.
    if ($image_uri === $sample_image_uri) {
      $image_uri = $target;
    }

    // Don't start generating the image if the derivative already exists or if
    // generation is in progress in another thread.
    if (!file_exists($derivative_uri)) {
      $lock_name = 'image_style_deliver:' . $image_style->id() . ':' . Crypt::hashBase64($image_uri);
      $lock_acquired = $this->lock->acquire($lock_name);
      if (!$lock_acquired) {
        // Tell client to retry again in 3 seconds. Currently no browsers are
        // known to support Retry-After.
        throw new ServiceUnavailableHttpException(3, 'Image generation in progress. Try again shortly.');
      }
    }

    // Try to generate the image, unless another thread just did it while we
    // were acquiring the lock.
    $success = FALSE;
    if (file_exists($derivative_uri)) {
      $success = TRUE;
    }
    elseif ($image_style->createDerivative($image_uri, $derivative_uri)) {
      $success = TRUE;
      try {
        $this->tinyPng->setFromFile($derivative_uri);
        $res = $this->tinyPng->saveTo($derivative_uri);
        $success = (bool) $res;
      }
      catch (\Exception $ex) {
        $this->serviceLogger->error($ex->getMessage());
      }
    }

    if (!empty($lock_acquired)) {
      $this->lock->release($lock_name);
    }

    if ($success) {
      $image = $this->imageFactory->get($derivative_uri);
      $uri = $image->getSource();
      $headers += [
        'Content-Type' => $image->getMimeType(),
        'Content-Length' => $image->getFileSize(),
      ];
      // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
      // sets response as not cacheable if the Cache-Control header is not
      // already modified. When $is_public is TRUE, the following sets the
      // Cache-Control header to "public".
      return new BinaryFileResponse($uri, 200, $headers, $is_public);
    }
    else {
      $this->logger->notice('Unable to generate the derived image located at %path.', ['%path' => $derivative_uri]);
      return new Response($this->t('Error generating image.'), 500);
    }
  }

  /**
   * Checks whether the provided source image exists.
   *
   * @param string $image_uri
   *   The URI for the source image.
   * @param bool $token_is_valid
   *   Whether a valid image token was supplied.
   *
   * @return bool
   *   Whether the source image exists.
   */
  private function sourceImageExists(string $image_uri, bool $token_is_valid): bool {
    $exists = file_exists($image_uri);

    // If the file doesn't exist, we can stop here.
    if (!$exists) {
      return FALSE;
    }

    if ($token_is_valid) {
      return TRUE;
    }

    if (StreamWrapperManager::getScheme($image_uri) !== 'public') {
      return TRUE;
    }

    $image_path = $this->fileSystem->realpath($image_uri);
    $private_path = Settings::get('file_private_path');
    if ($private_path) {
      $private_path = realpath($private_path);
      if ($private_path && str_starts_with($image_path, $private_path)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
