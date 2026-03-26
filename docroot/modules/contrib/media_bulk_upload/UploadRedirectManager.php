<?php

namespace Drupal\media_bulk_upload;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Upload Redirect Manager.
 */
class UploadRedirectManager {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Path to redirect to when editing is done.
   *
   * @var string
   */
  protected $editFinishPath;

  /**
   * Constructs UploadRedirectManager object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Drupal account object.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(AccountProxyInterface $account, SessionInterface $session) {
    $this->account = $account;
    $this->session = $session;
    $this->editFinishPath = '';
  }

  /**
   * Get the ids of the entities that have been created via bulk upload.
   */
  public function getCreatedEntityIds() {
    $key = 'media_bulk_uploads_' . $this->account->id();
    $createdEntityIds = $this->session->get($key);

    if (!$createdEntityIds) {
      $createdEntityIds = [];
      $this->session->set($key, $createdEntityIds);
    }

    return $createdEntityIds;
  }

  /**
   * Clear the list.
   */
  public function removeAllItems() {
    $this->session->set('media_bulk_uploads_' . $this->account->id(), []);
  }

  /**
   * Add a created entity id.
   */
  public function addItem($entityId) {
    $createdEntityIds = $this->getCreatedEntityIds();
    $createdEntityIds[$entityId] = $entityId;
    $this->session->set('media_bulk_uploads_' . $this->account->id(), $createdEntityIds);
  }

  /**
   * Remove a created entity id.
   */
  public function removeItem($entityId) {
    $createdEntityIds = $this->getCreatedEntityIds();
    // Remove item from cart.
    if (isset($createdEntityIds[$entityId])) {
        unset($createdEntityIds[$entityId]);
        $this->session->set('media_bulk_uploads_' . $this->account->id(), $createdEntityIds);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Alter the Media adit form.
   */
  public function alterMediaEditForm(&$form, FormStateInterface $form_state, $edit_finish_path) {
    // Set editFinishPath.
    $this->editFinishPath = $edit_finish_path;

    // Get current Media entity from route.
    $media = \Drupal::routeMatch()->getParameter('media');
    $media_id = $media->id();

    // Get the list of newly created media entities.
    $new_items = $this->getCreatedEntityIds();

    // Do nothing if current Media entity is not in the list.
    if (!isset($new_items[$media_id])) {
      return;
    }

    // Add count indicator to title.
    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = $media->bundle->entity;
    $total = count($new_items);
    $current = array_search($media_id, array_keys($new_items));
    $current++;
    $form['#title'] = t("Edit %type_label @label - $current/$total of newly uploaded items", [
      '%type_label' => $media_type->label(),
      '@label' => $media->label(),
    ]);

    // If this is not the last item.
    if ($current < $total) {
      // Change Submit button text.
      $form['actions']['submit']['#value'] = t('Save and edit next item');

      // Set redirect to the next media edit page.
      $form['actions']['submit']['#submit'][] = [$this, 'redirectNextHandler'];
    }

    // If this is the last item.
    if ($media_id == array_key_last($new_items)) {
      $form['actions']['submit']['#value'] = t('Save and finish editing');
      // Set redirect to the next media edit page.
      $form['actions']['submit']['#submit'][] = [$this, 'redirectFinishedHandler'];
    }

    // Add new action button to stop editing.
    $form['actions']['stop_editing'] = [
      '#type' => 'button',
      '#value' => 'Close and stop editing items',
      '#submit' => [[$this, 'redirectFinishedHandler']],
      '#executes_submit_callback' => TRUE,
      '#limit_validation_errors' => [],
    ];
  }

  /**
   * Submit handler to go to the next media edit page.
   */
  function redirectNextHandler($form, FormStateInterface $form_state) {
    // Get current Media entity from route.
    $media = \Drupal::routeMatch()->getParameter('media');
    $media_id = $media->id();
    // Get the media id of the next item.
    $array_keys = array_keys($this->getCreatedEntityIds());
    $next_id = $array_keys[array_search($media_id,$array_keys) + 1];
    $form_state->setRedirect('entity.media.edit_form', ['media' => $next_id]);
  }

  /**
   * Submit handler to finish editing
   */
  function redirectFinishedHandler($form, FormStateInterface $form_state) {
    // Clear all items.
    $this->removeAllItems();
    // Redirect to the path from 'Redirect to this path after finishing editing'.
    $route_name = Url::fromUserInput($this->editFinishPath)->getRouteName();
    $form_state->setRedirect($route_name);
  }
}