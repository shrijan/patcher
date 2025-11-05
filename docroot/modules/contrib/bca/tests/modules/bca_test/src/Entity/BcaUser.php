<?php

namespace Drupal\bca_test\Entity;

use Drupal\bca\Attribute\Bundle;
use Drupal\user\Entity\User;

#[Bundle(
  entityType: 'user',
)]
class BcaUser extends User {}
