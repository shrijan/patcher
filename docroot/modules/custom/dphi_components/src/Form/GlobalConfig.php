<?php

namespace Drupal\dphi_components\Form;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add configuration to make Left side navigation expandable if checked.
 *
 * @package Drupal\dpe_components\Form
 */
class GlobalConfig extends ConfigFormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * IconDialog constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'global_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dphi_components.settings',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dphi_components.settings');

    $form['left_side_nav_expand_collapse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Left side navigation expandable feature.'),
      '#description' => $this->t('Left side navigation will be expandable if checked.'),
      '#default_value' => $config->get('left_side_nav_expand_collapse'),
    ];
    $form['search_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filters on search page.'),
      '#description' => $this->t('Search page will show filters if checked.'),
      '#default_value' => \Drupal::keyValue('dphi_components')->get('search_filters'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dphi_components.settings')
    ->set('left_side_nav_expand_collapse', $form_state->getValue('left_side_nav_expand_collapse'))
    ->save();
    \Drupal::keyValue('dphi_components')->set('search_filters', $form_state->getValue('search_filters'));
    parent::submitForm($form, $form_state);
  }

}
