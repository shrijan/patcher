<?php

namespace Drupal\dphi_components\Entity;

interface ListItemsInterface {

  public function showImage(): ?string;

  public function showLabel(): bool;

  public function showPublishDate(): bool;

  public function showTags(): bool;

}
