<?php

/**
 * @file
 * Theme preprocess used to prepare Twig variables.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Link;

/**
 * Prepares term objects for Twig template.
 *
 * @param array $variables
 *   An associative array with preprocess variables for this theme.
 *   by theme_preprocess.
 */
function template_preprocess_hierarchical_term_formatter(array &$variables) {
  $terms = [];
  $variables['terms_objects'] = $variables['terms'];

  /** @var \Drupal\taxonomy\TermInterface $term */
  foreach ($variables['terms'] as $term) {
    if ($variables['link']) {
      $url = $term->toUrl();
      $link = Link::fromTextAndUrl($term->label(), $url)->toRenderable();
      $terms[] = \Drupal::service('renderer')->render($link);
    }
    else {
      $terms[] = $term->label();
    }
  }

  if ($variables['wrapper'] != 'none') {
    $count = 0;
    foreach ($terms as &$term) {
      $count++;
      $term = [
        '#type' => 'html_tag',
        '#tag' => in_array($variables['wrapper'], ['ol', 'ul']) ? 'li' : $variables['wrapper'],
        '#value' => $term,
        '#attributes' => [
          'class' => [
            Html::cleanCssIdentifier('taxonomy-term'),
            Html::cleanCssIdentifier("count {$count}"),
          ],
        ],
      ];
    }
  }

  unset($variables['link']);
  $variables['terms'] = $terms;
}
