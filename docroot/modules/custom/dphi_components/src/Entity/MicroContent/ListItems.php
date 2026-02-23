<?php

namespace Drupal\dphi_components\Entity\MicroContent;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Entity\ListItemsInterface;
use Drupal\dphi_components\Traits\ListItemsFieldTrait;
use Drupal\microcontent\Entity\MicroContent;

#[Bundle(
  entityType: 'microcontent',
  bundle: 'list_items',
)]
class ListItems extends MicroContent implements ListItemsInterface {

  use ListItemsFieldTrait;

}
