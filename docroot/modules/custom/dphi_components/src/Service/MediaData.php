<?php

namespace Drupal\dphi_components\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class MediaData {

  public function __construct(
    protected EntityTypeBundleInfo $bundleInfo,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  public function mediaFolderData(): array {
    $output = [[
      'id' => 'root',
      'folder' => 'Root (not in a folder)',
      'weight' => 0,
    ]];

    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $termIds = $termStorage->getQuery()
      ->condition('vid', 'media_directory')
      ->accessCheck()
      ->execute();
    $terms = $termStorage->loadMultiple($termIds);

    foreach ($terms as $term) {
      $output[] = [
        'id' => $term->id(),
        'folder' => $term->label(),
        'parentId' => $term->get('parent')->first()->getValue()['target_id'] ?: 'root',
        'weight' => $term->get('weight')->first()->getValue()['value'],
      ];
    }

    return $output;
  }

  public function mediaData(string $folderId = 'root'): array {
    $output = [];

    $bundleInfo = $this->bundleInfo->getBundleInfo('media');
    $mediaItems = $this->mediaInFolder($folderId);

    foreach ($mediaItems as $mediaItem) {
      $author = $mediaItem->getOwner() ?? NULL;
      $output[] = [
        'id' => $mediaItem->id(),
        'title' => $mediaItem->label(),
        'type' => $bundleInfo[$mediaItem->bundle()]['label'],
        'author' => $author ? $author->label() : '',
        'published' => $mediaItem->get('status')
          ->first()
          ->getValue()['value'],
        'lastUpdated' => (int) $mediaItem->get('changed')
          ->first()
          ->getValue()['value'],
        'view' => $mediaItem->toUrl('canonical')->toString(),
        'edit' => $mediaItem->toUrl('edit-form')->toString(),
      ];
    }

    return $output;
  }

  protected function mediaInFolder(string $folderId): array {
    $mediaStorage = $this->entityTypeManager->getStorage('media');
    $mediaQuery = $mediaStorage->getQuery('AND')
      ->accessCheck();

    // If the value is 'All', then we show only elements with empty value.
    if ($folderId === 'root') {
      $mediaQuery->condition('directory', null, 'IS NULL');
    }
    else {
      // Specific folder.
      $mediaQuery->condition('directory', $folderId);
    }

    $mediaIds = $mediaQuery->execute();

    return $mediaStorage->loadMultiple($mediaIds);
  }

  public function changeMediaItemFolder(int $mediaId, string $termId): void {
    $mediaStorage = $this->entityTypeManager->getStorage('media');
    $mediaItem = $mediaStorage->load($mediaId);

    if ($termId === 'root') {
      $mediaItem->set('directory', null);
    } else {
      $mediaItem->set('directory', $termId);
    }

    $validation = $mediaItem->validate();
    if ($validation->count()) {
      $violation = $validation->offsetGet(0);
      throw new \Error($violation->getMessage());
    }

    $mediaItem->save();
  }

}
