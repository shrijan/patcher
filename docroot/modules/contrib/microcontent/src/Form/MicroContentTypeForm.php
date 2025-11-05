<?php

namespace Drupal\microcontent\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\microcontent\Entity\MicroContentType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for editing micro-content types.
 */
class MicroContentTypeForm extends EntityForm {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('microcontent'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs a MicroContentTypeForm.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   */
  final public function __construct(LoggerInterface $logger, MessengerInterface $messenger) {
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\microcontent\Entity\MicroContentTypeInterface $microcontent_type */
    $microcontent_type = $this->entity;

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $microcontent_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $microcontent_type->id(),
      '#machine_name' => [
        'exists' => [MicroContentType::class, 'load'],
        'source' => ['name'],
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$microcontent_type->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#default_value' => $microcontent_type->getDescription(),
      '#description' => $this->t('Describe this micro-content type. The text will be displayed on the <em>Micro-content types</em> administration overview page.'),
      '#title' => $this->t('Description'),
    ];

    $form['type_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Type class'),
      '#maxlength' => 255,
      '#default_value' => $microcontent_type->getTypeClass(),
      '#description' => $this->t('Provide a CSS class that will be added to the <em>Add micro-content</em> page to allow your admin theme to provide a background image to help content-editors distinguish between the different micro-content types.'),
    ];

    $form['revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $microcontent_type->shouldCreateNewRevision(),
      '#description' => $this->t('Create a new revision by default for this micro-content type.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $microcontent_type = $this->entity;
    $microcontent_type->setNewRevision($form_state->getValue(['revision']));
    $status = $microcontent_type->save();

    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    $form_state->setRedirectUrl($microcontent_type->toUrl('collection'));
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Micro-content type %label has been updated.', ['%label' => $microcontent_type->label()]));
      $this->logger->notice('Micro-content type %label has been updated.', [
        '%label' => $microcontent_type->label(),
        'link' => $edit_link,
      ]);
      return $status;
    }
    $this->messenger()->addStatus($this->t('Micro-content type %label has been added.', ['%label' => $microcontent_type->label()]));
    $this->logger->notice('Micro-content type %label has been added.', [
      '%label' => $microcontent_type->label(),
      'link' => $edit_link,
    ]);
    return $status;
  }

}
