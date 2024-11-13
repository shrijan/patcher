<?php

namespace Drupal\tinypng\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
    TinyPngInterface $tiny_png
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
  public function deliver(Request $request, $scheme, ImageStyleInterface $image_style) {
    // If compression is not enabled for image style use core image deliver
    // method.
    if (!$image_style->getThirdPartySetting('tinypng', 'tinypng_compress')) {
      return parent::deliver($request, $scheme, $image_style);
    }

    $target = $request->query->get('file');
    $image_uri = $scheme . '://' . $target;
    // Check that the style is defined, the scheme is valid, and the image
    // derivative token is valid. Sites which require image derivatives to be
    // generated without a token can set the
    // 'image.settings:allow_insecure_derivatives' configuration to TRUE to
    // bypass the latter check, but this will increase the site's vulnerability
    // to denial-of-service attacks. To prevent this variable from leaving the
    // site vulnerable to the most serious attacks, a token is always required
    // when a derivative of a style is requested.
    // The $target variable for a derivative of a style has
    // styles/<style_name>/... as structure, so we check if the $target variable
    // starts with styles/.
    $valid = !empty($image_style) && $this->streamWrapperManager->isValidScheme($scheme);
    if (
      !$this->config('image.settings')->get('allow_insecure_derivatives')
      || strpos(ltrim($target, '\/'), 'styles/') === 0
    ) {
      $valid &= hash_equals($image_style->getPathToken($image_uri), $request->query->get(IMAGE_DERIVATIVE_TOKEN, ''));
    }
    if (!$valid) {
      // Return a 404 (Page Not Found) rather than a 403 (Access Denied) as the
      // image token is for DDoS protection rather than access checking. 404s
      // are more likely to be cached (e.g. at a proxy) which enhances
      // protection from DDoS.
      throw new NotFoundHttpException();
    }

    $derivative_uri = $image_style->buildUri($image_uri);
    $headers = [];

    // If using the private scheme, let other modules provide headers and
    // control access to the file.
    if ($scheme == 'private') {
      $headers = $this->moduleHandler()->invokeAll('file_download', [$image_uri]);
      if (in_array(-1, $headers) || empty($headers)) {
        throw new AccessDeniedHttpException();
      }
    }

    // Don't try to generate file if source is missing.
    if (!file_exists($image_uri)) {
      // If the image style converted the extension, it has been added to the
      // original file, resulting in filenames like image.png.jpeg. So to find
      // the actual source image, we remove the extension and check if that
      // image exists.
      $path_info = pathinfo(StreamWrapperManager::getTarget($image_uri));
      $converted_image_uri = sprintf('%s://%s%s%s', $this->streamWrapperManager->getScheme($derivative_uri), $path_info['dirname'], DIRECTORY_SEPARATOR, $path_info['filename']);
      if (!file_exists($converted_image_uri)) {
        $this->logger->notice('Source image at %source_image_path not found while trying to generate derivative image at %derivative_path.', [
          '%source_image_path' => $image_uri,
          '%derivative_path' => $derivative_uri,
        ]);
        return new Response($this->t('Error generating image, missing source file.'), 404);
      }
      else {
        // The converted file does exist, use it as the source.
        $image_uri = $converted_image_uri;
      }
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
      // already modified. We pass in FALSE for non-private schemes for the
      // $public parameter to make sure we don't change the headers.
      return new BinaryFileResponse($uri, 200, $headers, $scheme !== 'private');
    }
    else {
      $this->logger->notice('Unable to generate the derived image located at %path.', ['%path' => $derivative_uri]);
      return new Response($this->t('Error generating image.'), 500);
    }
  }

}
