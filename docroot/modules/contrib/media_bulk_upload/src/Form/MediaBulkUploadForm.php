<?php

namespace Drupal\media_bulk_upload\Form;

use Drupal;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Error;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface;
use Drupal\media_bulk_upload\MediaSubFormManager;
use Drupal\media_bulk_upload\UploadRedirectManager;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Class BulkMediaUploadForm.
 *
 * @package Drupal\media_upload\Form
 */
class MediaBulkUploadForm extends FormBase {

  /**
   * Media Type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaTypeStorage;

  /**
   * Media Bulk Config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaBulkConfigStorage;

  /**
   * Media entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * File entity storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * Media SubForm Manager.
   *
   * @var \Drupal\media_bulk_upload\MediaSubFormManager
   */
  protected $mediaSubFormManager;

  /**
   * The max file size for the media bulk form.
   *
   * @var string
   */
  protected $maxFileSizeForm;

  /**
   * The allowed extensions for the media bulk form.
   *
   * @var array
   */
  protected $allowed_extensions = [];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The mime type guesser.
   *
   * @var \Symfony\Component\Mime\MimeTypeGuesserInterface
   */
  private $mimeTypeGuesser;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The file repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * The upload redirect manager.
   *
   * @var \Drupal\media_bulk_upload\UploadRedirectManager
   */
  protected $uploadRedirectManager;
  /**
   * If we need to redirect to media edit page after the bulk upload.
   *
   * @var bool
   */
  protected $editAfterUpload = FALSE;

  /**
   * The file validation service.
   *
   * @var \Drupal\file\Validation\FileValidatorInterface
   */
  protected $fileValidator;

  /**
   * BulkMediaUploadForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\media_bulk_upload\MediaSubFormManager $mediaSubFormManager
   *   Media Sub Form Manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current User.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\file\Validation\FileValidatorInterface $validator
   *   File validation service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MediaSubFormManager $mediaSubFormManager, AccountProxyInterface $currentUser, MessengerInterface $messenger, FileRepositoryInterface $fileRepository, FileValidatorInterface $validator) {
    $this->mediaTypeStorage = $entityTypeManager->getStorage('media_type');
    $this->mediaBulkConfigStorage = $entityTypeManager->getStorage('media_bulk_config');
    $this->mediaStorage = $entityTypeManager->getStorage('media');
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->mediaSubFormManager = $mediaSubFormManager;
    $this->currentUser = $currentUser;
    $this->messenger = $messenger;
    $this->fileRepository = $fileRepository;
    $this->maxFileSizeForm = '';
    $this->fileValidator = $validator;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_bulk_upload.subform_manager'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('file.repository'),
      $container->get('file.validator')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'media_bulk_upload_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface|null $media_bulk_config
   *   The media bulk configuration entity.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state, MediaBulkConfigInterface $media_bulk_config = NULL) {
    $mediaBulkConfig = $media_bulk_config;

    if ($mediaBulkConfig === NULL) {
      return $form;
    }

    $mediaTypeManager = $this->mediaSubFormManager->getMediaTypeManager();
    $mediaTypes = $this->mediaSubFormManager->getMediaTypeManager()->getBulkMediaTypes($mediaBulkConfig);
    $mediaTypeLabels = [];

    foreach ($mediaTypes as $mediaType) {
      $extensions = $mediaTypeManager->getMediaTypeExtensions($mediaType);
      natsort($extensions);
      $this->addAllowedExtensions($extensions);

      $maxFileSize = $mediaTypeManager->getTargetFieldMaxSize($mediaType);
      $mediaTypeLabels[] = $mediaType->label() . ' (max ' . $maxFileSize . '): ' . implode(', ', $extensions);
      if (!empty($maxFileSize) && $this->isMaxFileSizeLarger($maxFileSize)) {
        $this->setMaxFileSizeForm($maxFileSize);
      }
    }

    if (empty($this->maxFileSizeForm)) {
      $this->maxFileSizeForm = $this->mediaSubFormManager->getDefaultMaxFileSize();
    }

    $form['#tree'] = TRUE;
    $form['information_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'media-bulk-upload-information-wrapper',
        ],
      ],
    ];
    $form['information_wrapper']['information_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Information'),
      '#attributes' => [
        'class' => [
          'form-control-label',
        ],
        'for' => 'media_bulk_upload_information',
      ],
    ];

    $form['information_wrapper']['information'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Media Types:'),
      '#items' => $mediaTypeLabels,
    ];

    if (count($mediaTypes) > 1) {
      $form['information_wrapper']['warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#id' => 'media_bulk_upload_information',
        '#name' => 'media_bulk_upload_information',
        '#value' => $this->t('Please be aware that if file extensions overlap between the media types that are available in this upload form, that the media entity will be assigned automatically to one of these types.'),
      ];
    }

    $validators = array(
      'FileExtension' => ['extensions' => implode(' ', $this->allowed_extensions)],
      'FileSizeLimit' => ['fileLimit' => Bytes::toNumber($this->maxFileSizeForm)],
    );

    $form['file_upload'] = [
      '#type' => 'managed_file',
      '#multiple' => TRUE,
      '#title' => $this->t('File Upload'),
      '#required' => TRUE,
      '#description' => $this->t('Click or drop your files here. You can upload up to <strong>@limit</strong> files at once.', ['@limit' => ini_get('max_file_uploads')]),
      '#upload_validators' => $validators,
      '#upload_location' => $mediaBulkConfig->get('upload_location'),
      '#show_alt' => (boolean) $mediaBulkConfig->get('show_alt'),
      '#alt_required' => (boolean) $mediaBulkConfig->get('alt_required'),
      '#show_title' => (boolean) $mediaBulkConfig->get('show_title'),
    ];

    if ($this->mediaSubFormManager->validateMediaFormDisplayUse($mediaBulkConfig)) {
      $form['fields'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Fields'),
        'shared' => [
          '#field_parents' => ['fields', 'shared'],
          '#parents' => ['fields', 'shared'],
        ],
      ];
      $this->mediaSubFormManager->buildMediaSubForm($form['fields']['shared'], $form_state, $mediaBulkConfig);
    }

    $form['media_bundle_config'] = [
      '#type' => 'value',
      '#value' => $mediaBulkConfig->id(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Add allowed extensions.
   *
   * @param array $extensions
   *   Allowed Extensions.
   *
   * @return $this
   *   MediaBulkUploadForm.
   */
  protected function addAllowedExtensions(array $extensions) {
    $this->allowed_extensions = array_unique(array_merge($this->allowed_extensions, $extensions));

    return $this;
  }

  /**
   * Validate if a max file size is bigger then the current max file size.
   *
   * @param string $MaxFileSize
   *   File Size.
   *
   * @return bool
   *   TRUE if the given size is larger than the one that is set.
   */
  protected function isMaxFileSizeLarger($MaxFileSize) {
    $size = Bytes::toNumber($MaxFileSize);
    $currentSize = Bytes::toNumber($this->maxFileSizeForm);

    return ($size > $currentSize);
  }

  /**
   * Set the max File size for the form.
   *
   * @param string $newMaxFileSize
   *   File Size.
   *
   * @return $this
   *   MediaBulkUploadForm.
   */
  protected function setMaxFileSizeForm($newMaxFileSize) {
    $this->maxFileSizeForm = $newMaxFileSize;

    return $this;
  }

  /**
   * Submit handler to create the file entities and media entities.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $mediaBundleConfigId = $values['media_bundle_config'];

    /** @var MediaBulkConfigInterface $mediaBulkConfig */
    $mediaBulkConfig = $this->mediaBulkConfigStorage->load($mediaBundleConfigId);
    $filesData = $values['file_upload'];

    // Did not figure out yet how to access config in batchFinished(), so saving the value here.
    $this->editAfterUpload = $mediaBulkConfig->edit_after_upload;
    
    
    $module_handler = $this->moduleHandler ?: \Drupal::moduleHandler();
    $module_handler->alter('media_bulk_upload_files', $filesData, $mediaBulkConfig);
   // die();

    if (empty($filesData)) {
      return;
    }

    $metadata = [];
    foreach ($filesData as $file) {
      $metadata[$file['file']->id()] = $file['metadata'];
    }

    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->fileStorage->loadMultiple(array_keys($filesData));

    $mediaTypes = $this->mediaSubFormManager->getMediaTypeManager()->getBulkMediaTypes($mediaBulkConfig);
    $mediaType = reset($mediaTypes);
    $mediaFormDisplay = $this->mediaSubFormManager->getMediaFormDisplay($mediaBulkConfig, $mediaType);

    $this->prepareFormValues($form_state);

    $batchOperations = [];
    $operationId = 1;
    foreach ($files as $file) {
      $batchOperations[] = [
        [$this, 'batchOperation'],
        [
          $operationId,
          [
            'media_bulk_config' => $mediaBulkConfig,
            'media_form_display' => $mediaFormDisplay,
            'file' => $file,
            'metadata' => $metadata[$file->id()],
            'form' => $form,
            'form_state' => $form_state,
          ],
        ],
      ];
      $operationId++;
    }
    $operationsCount = count($batchOperations);
    $batch = [
      'title' => $this->formatPlural(
        $operationsCount,
        'Preparing 1 media item',
        'Preparing @count media items', ['@count' => $operationsCount]
      ),
      'operations' => $batchOperations,
      'finished' => [$this, 'batchFinished'],
    ];
    batch_set($batch);
  }

  /**
   * Batch operation callback.
   *
   * @param string $id
   *   Batch operation id.
   * @param array $operation_details
   *   Batch operation details.
   * @param array $context
   *   Batch context.
   */
  public function batchOperation($id, array $operation_details, array &$context) {
    $mediaBulkConfig = $operation_details['media_bulk_config'];
    $mediaFormDisplay = $operation_details['media_form_display'];
    $file = $operation_details['file'];
    $metadata = $operation_details['metadata'];
    $form = $operation_details['form'];
    $form_state = $operation_details['form_state'];
    try {
      $media = $this->processFile($mediaBulkConfig, $file, $metadata);
      if ($this->mediaSubFormManager->validateMediaFormDisplayUse($operation_details['media_bulk_config'])) {
        $extracted = $mediaFormDisplay->extractFormValues($media, $form['fields']['shared'], $form_state);
        $this->copyFormValuesToEntity($media, $extracted, $form_state);
      }
      $media->save();

      // Save the created media entity ID for if we need to redirect to the media edit page afterwards.
      $upload_redirect_manager = $this->uploadRedirectManager ?: \Drupal::service('media_bulk_upload.redirect_manager');
      $upload_redirect_manager->addItem($media->id());

      $context['results'][] = $id;

      $context['message'] = $this->t('Processing file @id.',
        [
          '@id' => $id,
        ]
      );
    } catch (Exception $e) {
      if (method_exists(Error::class, 'logException')) {
        Error::logException($this->getLogger('media_bulk_upload'), $e);
      }
      else {
        // @phpstan-ignore-next-line
        watchdog_exception('media_bulk_upload', $e);
      }
    }
  }

  /**
   * Batch finished callback.
   *
   * @param boolean $success
   *   Batch success.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   Batch operations.
   */
  public function batchFinished($success, array $results, array $operations) {
    if ($success) {
      // Check if we need to redirect to media edit page.
      $upload_redirect_manager = $this->uploadRedirectManager ?: \Drupal::service('media_bulk_upload.redirect_manager');
      $media_ids = $upload_redirect_manager->getCreatedEntityIds();
      if ($this->editAfterUpload && !empty($media_ids)) {
        // Custom message when redirecting.
        $this->messenger()->addMessage($this->t('@count media have been created. You can now make changes to each item one by one.', ['@count' => count($results)]));
        // Redirect to the edit page of the first media entity that was created.
        $media_id = reset($media_ids);
        return new RedirectResponse(Url::fromRoute('entity.media.edit_form', ['media' => $media_id], [])->toString());
      }

      // Default message when not redirecting.
      $this->messenger()->addMessage($this->t('@count media have been created.', ['@count' => count($results)]));
    }

    if (!$success) {
      $errorOperation = reset($operations);
      $this->messenger()->addError(
        $this->t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $errorOperation[0],
            '@args' => print_r($errorOperation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * Process a file upload.
   *
   * Will create a file entity and prepare a media entity with data.
   *
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig
   *   Media Bulk Config.
   * @param \Drupal\file\FileInterface $file
   *   File entity.
   * @param array $metadata
   *   Additional metadata for the file.
   *
   * @return \Drupal\media\MediaInterface
   *   The unsaved media entity that is created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  protected function processFile(MediaBulkConfigInterface $mediaBulkConfig, FileInterface $file, array $metadata = []) {
    $filename = $file->getFilename();

    if (!$this->validateFile($file)) {
      $this->messenger()->addError($this->t('File :filename does not have a valid extension or filename.', [':filename' => $filename]));
      throw new Exception("File $filename does not have a valid extension or filename.");
    }

    $allowedMediaTypes = $this->mediaSubFormManager->getMediaTypeManager()
      ->getBulkMediaTypes($mediaBulkConfig);
    $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    $matchingMediaTypes = $this->mediaSubFormManager->getMediaTypeManager()
      ->getMediaTypeIdsByFileExtension($extension);

    $mediaTypes = array_intersect_key($matchingMediaTypes, $allowedMediaTypes);
    $mediaType = reset($mediaTypes);

    if (!$this->validateFileSize($mediaType, $file)) {
      $fileSizeSetting = $this->mediaSubFormManager->getMediaTypeManager()
        ->getTargetFieldMaxSize($mediaType);
      $mediaTypeLabel = $mediaType->label();
      $this->messenger()
        ->addError($this->t('File :filename exceeds the maximum file size of :file_size for media type :media_type exceeded.', [
          ':filename' => $filename,
          ':file_size' => $fileSizeSetting,
          ':media_type' => $mediaTypeLabel,
        ]));
      throw new Exception("File $filename exceeds the maximum file size of $fileSizeSetting for media type $mediaTypeLabel exceeded.");
    }

    if ($mediaType->getSource()->getPluginId() == 'image') {
      $errors = $this->validateImageResolution($mediaType, $file);
      if (!empty($errors)) {
        $this->messenger()->addError($this->t('File :filename has image resolution errors. Check the logs for more details.', [':filename' => $filename]));
        throw new \Exception('File image resolution errors: ' . implode(', ', $errors));
      }
    }

    $uri_scheme = $this->mediaSubFormManager->getTargetFieldDirectory($mediaType);
    $destination = $uri_scheme . '/' . $file->getFilename();
    $file_default_scheme = Drupal::config('system.file')->get('default_scheme') . '://';
    if ($uri_scheme === $file_default_scheme) {
      $destination = $uri_scheme . $file->getFilename();
    }

    if (!$this->fileRepository->move($file, $destination, FileExists::Rename)) {
      $this->messenger()->addError($this->t('File :filename could not be moved.', [':filename' => $filename]), 'error');
      throw new Exception('File entity could not be moved.');
    }

    $values = $this->getNewMediaValues($mediaType, $file, $metadata);
    /** @var \Drupal\media\MediaInterface $media */

    return $this->mediaStorage->create($values);
  }

  /**
   * Validate if the filename and extension are valid in the provided file info.
   *
   * @param \Drupal\file\FileInterface $file
   *
   * @return bool
   *   If the file info validates, returns true.
   */
  protected function validateFile(FileInterface $file) {
    return !(empty($file->getFilename()) || empty($file->getMimeType()));
  }

  /**
   * Check the size of a file.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type.
   * @param \Drupal\file\FileInterface $file
   *
   * @return bool
   *   True if max size for a given file do not exceeds max size for its type.
   */
  protected function validateFileSize(MediaTypeInterface $mediaType, FileInterface $file) {
    $fileSizeSetting = $this->mediaSubFormManager->getMediaTypeManager()->getTargetFieldMaxSize($mediaType);
    $fileSize = $file->getSize();
    $maxFileSize = !empty($fileSizeSetting)
      ? Bytes::toNumber($fileSizeSetting)
      : Environment::getUploadMaxSize();

    if ((int) $maxFileSize === 0) {
      return TRUE;
    }

    return $fileSize <= $maxFileSize;
  }

  /**
   * Validates the resolution of an image.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   The media type entity.
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   *
   * @return array
   *   Array of errors provided by fileValidator->validate.
   */
  protected function validateImageResolution(MediaTypeInterface $mediaType, FileInterface $file): array {
    $field_settings = $this->mediaSubFormManager
      ->getMediaTypeManager()
      ->getTargetFieldSettings($mediaType);

    $violations = $this->fileValidator->validate(
      File::create(['uri' => $file->getFileUri()]),
      [
        'FileImageDimensions' =>
          [
            'maxDimensions' => $field_settings['max_resolution'] ?? 0,
            'minDimensions' => $field_settings['min_resolution'] ?? 0,
          ],
      ],
    );
    $errors = [];
    foreach ($violations as $violation) {
      $errors[] = $violation->getMessage();
    }

    return $errors;
  }

  /**
   * Builds the array of all necessary info for the new media entity.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type ID.
   * @param \Drupal\file\FileInterface $file
   *   File entity.
   * @param array $metadata
   *   Additional metadata for the file.
   *
   * @return array
   *   Return an array describing the new media entity.
   */
  protected function getNewMediaValues(MediaTypeInterface $mediaType, FileInterface $file, array $metadata) {
    $targetFieldName = $this->mediaSubFormManager->getMediaTypeManager()
      ->getTargetFieldName($mediaType);

    // Get filename without extension.
    $pathinfo = pathinfo($file->getFilename());
    // When uploading, Drupal replaces spaces in filename with underscores.
    // Drupal also lowercases filenames.
    // For alt and title values, we replace it back.
    $clean_filename = ucfirst(str_replace("_", " ", $pathinfo['filename']));

    $media_data = ['bundle' => $mediaType->id(),
      'name' => $clean_filename,
      $targetFieldName => [
        'target_id' => $file->id(),
        'title' => $clean_filename,
        'alt' => $clean_filename,
      ],
    ];

    if (isset($metadata['title'])) {
      $media_data['name'] = $metadata['title'];
      $media_data[$targetFieldName]['title'] = $metadata['title'];
    }

    if (isset($metadata['alt'])) {
      $media_data[$targetFieldName]['alt'] = $metadata['alt'];
    }

    return $media_data;
  }

  /**
   * Copy the submitted values for the media subform to the media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   * @param array $extracted
   *   Extracted entity values.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   */
  protected function copyFormValuesToEntity(MediaInterface $media, array $extracted, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $name => $values) {
      if (isset($extracted[$name]) || !$media->hasField($name)) {
        continue;
      }
      $media->set($name, $values);
    }
  }

  /**
   * Prepare form submitted values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return $this
   *   Media Bulk Upload Form.
   */
  protected function prepareFormValues(FormStateInterface $form_state) {
    // If the shared name is empty, remove it from the form state.
    // Otherwise the extractFormValues function will override with an empty value.
    $shared = $form_state->getValue(['fields', 'shared']);
    if (empty($shared['name'][0]['value'])) {
      unset($shared['name']);
      $form_state->setValue(['fields', 'shared'], $shared);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate all uploaded files.
    $uploaded_files = $form_state->getValue(['file_upload', 'uploaded_files']);
    $media_bundle_config_id = $form_state->getValue(['media_bundle_config']);
    $media_bulk_config = $this->mediaBulkConfigStorage->load($media_bundle_config_id);

    if (empty($uploaded_files)) {
      $form_state->setErrorByName('file_upload', $this->t('No media files have been provided.'));
    }
    else {
      $show_alt = (boolean) $media_bulk_config->get('show_alt');
      $alt_required = (boolean) $media_bulk_config->get('alt_required');

      foreach ($uploaded_files as $uploaded_file) {
        $errors = [];

        // Validate file alt.
        //$mime = $this->mimeTypeGuesser->guessMimeType($uploaded_file['path']);
        $mime = NULL;
        if (!empty($uploaded_file['path'])) {
          if ($this->mimeTypeGuesser) {
            $mime = $this->mimeTypeGuesser->guessMimeType($uploaded_file['path']);
          }
          else {
            $mime = \Drupal::service('file.mime_type.guesser')->guessMimeType($uploaded_file['path']);
          }
        }
        if (
          strpos($mime, 'image/') !== FALSE
          && $show_alt
          && $alt_required
          && empty($uploaded_file['metadata']['alt'])
        ) {
          $errors[] = $this->t('Alt value for images is required.');
        }

        // Create a new file entity since some modules only validate new files.
        $file = $this->fileStorage->create([
          'uri' => $uploaded_file['path'],
        ]);

        // Let other modules perform validation on the new file.
        $module_handler = $this->moduleHandler ?: \Drupal::moduleHandler();
$errors = array_merge($errors, $module_handler->invokeAll('file_validate', [
  $file,
]));

        // Process any reported errors.
        if (!empty($errors)) {
          $form_state->setErrorByName('file_upload', 'Errors for file ' . $file->getFilename() . ': ' . implode(', ', $errors));

          try {
            // Delete the uploaded file if it has validation errors.
            $this->fileSystem->delete($uploaded_file['path']);
          }
          catch (Exception $e) {
            if (method_exists(Error::class, 'logException')) {
              Error::logException($this->getLogger('media_bulk_upload'), $e);
            }
            else {
              // @phpstan-ignore-next-line
              watchdog_exception('media_bulk_upload', $e);
            }
          }
        }
      }
    }
  }

}
