<?php

namespace Drupal\bca_test\Entity;

use Drupal\bca\Attribute\Bundle;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Entity\EntityTest;

#[Bundle(
  entityType: 'entity_test',
  bundle: 'bca_test_bundle_with_label',
  label: new TranslatableMarkup('Overridden label'),
)]
class BcaTestBundleWithLabel extends EntityTest {}
