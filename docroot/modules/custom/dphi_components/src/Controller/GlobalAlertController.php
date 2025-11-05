<?php

namespace Drupal\dphi_components\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class GlobalAlertController functionality.
 */
class GlobalAlertController extends ControllerBase {

  /**
   * Display the Alert by Ajax Call
   */
  public function displayAlert() {
    $query = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', 'global_alert')
        ->condition('status', 1)
        ->condition('field_display_flag', 1);
    $results = $query->execute();
    $alert_data = [];
    foreach ($results as $nid) {
      $alert = Node::load($nid);
      $button_class = 'nsw-button--white nsw-bg--white nsw-text--dark';
      switch ($alert->field_alert_type->value) {
        case 'critical':
          $alert_type_class = 'nsw-global-alert--critical';
          break;
        case 'light':
          $alert_type_class = 'nsw-global-alert--light';
          $button_class = 'nsw-button--info nsw-bg--info-dark nsw-text--light';
          break;
        default:
          $alert_type_class = '';
      }
      $cta_url = '';
      $cta_text = '';
      if (!empty($alert->field_cta->uri)) {
        $url = Url::fromUri($alert->field_cta->uri);
        if (!empty($url)) {
          $cta_url = $url->toString();
        }
        $cta_text = $alert->field_cta->title;
      }
      $alert_data[$nid] = [
        'title' => $alert->title->value,
        'body' => $alert->body->value,
        'cta_url' => $cta_url,
        'cta_text' => $cta_text,
        'alert_type_class' => $alert_type_class,
        'button_class' => $button_class
      ];
    }
    return new JsonResponse($alert_data);
  }
}
