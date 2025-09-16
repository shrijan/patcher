<?php

/**
 * Set defaults for focal points on images.
 */
function dphi_components_post_update_set_focal_point_defaults(&$sandbox) {
  \Drupal\dphi_components\Service\FocalPointDefault::update10201($sandbox);
}
