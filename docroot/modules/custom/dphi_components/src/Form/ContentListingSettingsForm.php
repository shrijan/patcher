<?php

namespace Drupal\dphi_components\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentListingSettingsForm extends ConfigFormBase {

  protected $contentTypesTermsService;

  public function __construct($contentTypesTermsService) {
      $this->contentTypesTermsService = $contentTypesTermsService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('dphi_components.content_listing.content_types_terms_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dphi_components.content_listing.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_listing_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dphi_components.content_listing.settings');
    $excluded_values = $config->get('excluded_values') ?: [];

    // Use the created service to get content types and terms without exclusions.
    $options = $this->contentTypesTermsService->getContentTypesAndTerms(false);

    $form['excluded_values'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Excluded Content Types and Categories'),
        '#options' => $options,
        '#default_value' => $excluded_values,
        '#description' => $this->t('Select the content types and categories to exclude in the Content Listing component.'),
    ];

    return parent::buildForm($form, $form_state);
  }


  /**
 * {@inheritdoc}
 */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the excluded values in the 'content_listing.settings' configuration.
    $excluded_values = array_filter($form_state->getValue('excluded_values'));
    $this->config('dphi_components.content_listing.settings')
        ->set('excluded_values', $excluded_values)
        ->save();

    $this->messenger()->addMessage($this->t('The excluded content types and categories have been updated.'));
  }
}
