<?php

namespace Drupal\bca_annotation_test\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Bundle class with label.
 *
 * @Bundle(
 *   entity_type = "entity_test",
 *   bundle = "bca_test_bundle_with_label",
 *   label = @Translation("Overridden label")
 * )
 */
class BcaTestBundleWithLabel extends EntityTest {}
