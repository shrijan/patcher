<?php

namespace Drupal\decision_tool\Service;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ProcessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Service class to alter feeds data
 */
class FeedsService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      FeedsEvents::PROCESS => 'process'
    ];
  }

  public function process(ProcessEvent $event) {
    $fields = [];
    $feed_bundle = $event->getFeed()->bundle();
    if ($feed_bundle == 'question_import') {
      $fields = ['title', 'question', 'response', 'tooltip'];
    } else if ($feed_bundle == 'answer_import') {
      $fields = ['text'];
    }

    if (!$fields) {
      return;
    }
    $item = $event->getItem();
    foreach ($fields as $field) {
      $value = $item->get($field);
      if ($value) {
        $value = str_replace(chr(146), '\'', $value);
        $item->set($field, $value);
      }
    }
  }
}
