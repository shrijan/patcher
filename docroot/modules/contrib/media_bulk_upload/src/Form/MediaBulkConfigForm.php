<?php

namespace Drupal\media_bulk_upload\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Routing\RequestContext;

/**
 * Class MediaBulkConfigForm.
 */
class MediaBulkConfigForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * Entity Display Repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * MediaBulkConfigForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   Entity Display repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The path alias manager.
   * @param \Drupal\Core\Routing\RequestContext $requestContext
   *   The request context.
   */
  public function __construct(EntityDisplayRepositoryInterface $entityDisplayRepository, MessengerInterface $messenger, ConfigFactoryInterface $configFactory, AliasManagerInterface $aliasManager, RequestContext $requestContext) {
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->messenger = $messenger;
    $this->configFactory = $configFactory;
    $this->aliasManager = $aliasManager;
    $this->requestContext = $requestContext;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_display.repository'),
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig */
    $mediaBulkConfig = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $mediaBulkConfig->label(),
      '#description' => $this->t("Label for the Media Bulk Config."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $mediaBulkConfig->id(),
      '#machine_name' => [
        'exists' => '\Drupal\media_bulk_upload\Entity\MediaBulkConfig::load',
      ],
      '#disabled' => !$mediaBulkConfig->isNew(),
    ];

    $media_types = $mediaBulkConfig->get('media_types');
    $form['media_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Media Types'),
      '#description' => $this->t('Choose the media types that will be
        used to create new media entities based on matching extensions. Please be
        aware that if file extensions overlap between the media types that are
        chosen, that the media entity will be assigned automatically to one of
        these types.'),
      '#options' => $this->getMediaTypeOptions(),
      '#default_value' => isset($media_types) ? $media_types : [],
      '#size' => 20,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    $show_alt = $mediaBulkConfig->get('show_alt');
    $form['show_alt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Alt text input for uploaded files.'),
      '#description' => $this->t('Check if you want to be able to add Alt text
        to the uploaded files. Please do NOT use [ or ] symbols in your text.'),
      '#default_value' => (boolean) $show_alt,
    ];

    $alt_required = $mediaBulkConfig->get('alt_required');
    $form['alt_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make Alt text input required.'),
      '#description' => $this->t('Check if you want the Alt text to be required for image files.'),
      '#default_value' => (boolean) $alt_required,
    ];

    $show_title = $mediaBulkConfig->get('show_title');
    $form['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show title text input for uploaded files.'),
      '#description' => $this->t('Check if you want to be able to add title text
        to the uploaded files. Please do NOT use [ or ] symbols in your text.'),
      '#default_value' => (boolean) $show_title,
    ];

    $form['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Form Mode'),
      '#description' => $this->t('Based on the form mode the upload form
        can be enriched with fields that are available, improving the speed and
        usability to add (meta)data to your media entities.'),
      '#options' => $this->entityDisplayRepository->getFormModeOptions('media'),
      "#empty_option" => t('- None -'),
      '#default_value' => $mediaBulkConfig->get('form_mode'),
    ];

    $form['upload_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upload location'),
      '#description' => $this->t('Location to initially upload the files before they are moved to the determined
      location in the media types.'),
      '#default_value' => $mediaBulkConfig->get('upload_location'),
    ];

    $form['edit_after_upload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Edit each Media item after upload'),
      '#description' => $this->t('When checked, you will be redirected to the edit page of each Media item that was created after upload.'),
      '#default_value' => $mediaBulkConfig->get('edit_after_upload'),
    ];

    $edit_finish_path = $mediaBulkConfig->get('edit_finish_path') ?: $this->aliasManager->getAliasByPath($this->configFactory->get('system.site')->get('page.front'));
    $form['edit_finish_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect to this path after finishing editing'),
      '#default_value' => $edit_finish_path,
      '#size' => 40,
      '#description' => $this->t('Optionally, specify a relative URL. Leave blank to redirect to the default front page.'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];

    return $form;
  }

  /**
   * Get the available media type options.
   */
  private function getMediaTypeOptions() {
    $mediaTypeStorage = $this->entityTypeManager->getStorage('media_type');
    $mediaTypes = $mediaTypeStorage->loadMultiple();

    foreach ($mediaTypes as $mediaType) {
      $mediaTypeOptions[$mediaType->id()] = $mediaType->label();
    }
    natsort($mediaTypeOptions);

    return $mediaTypeOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $media_bulk_config = $this->entity;
    $status = $media_bulk_config->save();

    $save_message = $this->t('Saved the %label Media Bulk Config.', [
      '%label' => $media_bulk_config->label(),
    ]);

    if ($status == SAVED_NEW) {
      $save_message = $this->t('Created the %label Media Bulk Config.', [
        '%label' => $media_bulk_config->label(),
      ]);
    }

    $this->messenger->addMessage($save_message);
    $form_state->setRedirectUrl($media_bulk_config->toUrl('collection'));
  }

}
