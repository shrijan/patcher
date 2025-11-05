<?php

namespace Drupal\file_rename\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form to rename a file.
 *
 * @property \Drupal\file\Entity\File $entity
 */
class FileRenameForm extends ContentEntityForm {

  /**
   * FileSystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * EventDispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setModuleHandler($container->get('module_handler'));
    $instance->fileSystem = $container->get('file_system');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Rename %filename', [
      '%filename' => $this->entity->getFilename(),
    ]);

    $pathinfo = pathinfo($this->entity->getFileUri());

    $is_image = substr($this->entity->getMimeType(), 0, 5) === 'image';

    $form['new_filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File name'),
      '#default_value' => $pathinfo['filename'],
      '#size' => 45,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#field_suffix' => "<strong>.{$pathinfo['extension']}</strong>",
    ];

    $form['info'] = [
      '#markup' => $this->t('<p>This page opened in a new tab.<br>After you save this form, <b>return to your original tab</b> to make sure you save any changes you made there.</p>'),
    ];

    $form['usages'] = [
      '#markup' => '<p><a href="/admin/content/files/usage/' . $this->entity->id() . '" target="_blank">' . $this->t('See file usages') . '</a></p>',
    ];

    $form['prompt'] = [
      '#markup' => $this->t('Note: if image styles are used, they will be flushed automatically.'),
      '#access' => $is_image,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pathinfo = pathinfo($this->entity->getFileUri());
    $source_file_uri = $this->entity->getFileUri();

    if (!file_exists($source_file_uri)) {
      // Show an error if no file on disc.
      $form_state->setError($form['new_filename'], $this->t('Source file %file was not found', [
        '%file' => $source_file_uri,
      ]));
    }

    $new_filename = $form_state->getValue('new_filename');
    $new_basename = $new_filename . '.' . $pathinfo['extension'];

    if ($new_basename !== $this->entity->getFilename()) {
      // File renamed.
      if ($new_basename !== basename($new_basename) || strpos($new_basename, '\\') !== FALSE) {
        // If filename contains a slash or a backslash.
        $form_state->setError($form['new_filename'], $this->t('Value must be a filename with no path information'));
      }
      else {
        // Dispatching a event to use default filename validation.
        $event = new FileUploadSanitizeNameEvent($new_basename, $pathinfo['extension']);
        $this->eventDispatcher->dispatch($event);

        if ($event->isSecurityRename()) {
          // If new filename contains forbidden characters.
          $form_state->setError($form['new_filename'], $this->t('File name is invalid'));
        }
      }

      $new_file_path = $this->getRenamedFilePath($form_state);
      if (file_exists($new_file_path)) {
        // File with given name already on disc.
        $form_state->setError($form['new_filename'], $this->t('File %filename already exists in the same directory.', [
          '%filename' => $new_filename,
        ]));
      }
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $pathinfo = pathinfo($this->entity->getFileUri());
    $filename_new = $form_state->getValue('new_filename') . '.' . $pathinfo['extension'];

    $reminder = $this->t("Don't forget to return to your original tab and save any changes you made there.");
    if ($filename_new != $this->entity->getFilename()) {
      $filepath_new = $this->getRenamedFilePath($form_state);
      // Invoke pre-rename hooks.
      $this->moduleHandler->invokeAll('file_prerename', [$this->entity]);
      // Rename existing file.
      $this->fileSystem->move($this->entity->getFileUri(), $filepath_new, FileSystemInterface::EXISTS_REPLACE);
      $log_args = [
        '%old' => $this->entity->getFilename(),
        '%new' => $filename_new,
      ];
      // Update file entity.
      $this->entity->setFilename($filename_new);
      $this->entity->setFileUri($filepath_new);
      $status = $this->entity->save();
      // Notify and log.
      $this->messenger()->addStatus($this->t('File %old was renamed to %new', $log_args));
      $this->logger('file_entity')->info($this->t('File %old renamed to %new'), $log_args);
      // Invoke hooks if there are some.
      $this->moduleHandler->invokeAll('file_rename', [$this->entity]);

      $this->messenger()->addWarning($reminder);
      return $status;
    }
    $this->messenger()->addWarning($reminder);
  }

  /**
   * Get Renamed File Path.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FileRename form state.
   *
   * @return string
   *   File name after rename.
   */
  protected function getRenamedFilePath(FormStateInterface $form_state) {
    $pathinfo = pathinfo($this->entity->getFileUri());
    $old_filename = $pathinfo['filename'];
    $new_filename = $form_state->getValue('new_filename');
    // Path after renaming.
    return str_replace($old_filename, $new_filename, $this->entity->getFileUri());
  }

}
