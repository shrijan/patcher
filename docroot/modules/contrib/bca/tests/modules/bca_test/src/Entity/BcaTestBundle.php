<?php

namespace Drupal\bca_test\Entity;

use Drupal\bca\Attribute\Bundle;
use Drupal\entity_test\Entity\EntityTest;

#[Bundle(
  entityType: 'entity_test',
  bundle: 'bca_test_bundle',
)]
class BcaTestBundle extends EntityTest {}
