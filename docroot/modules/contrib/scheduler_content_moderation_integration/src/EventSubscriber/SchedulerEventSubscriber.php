<?php

namespace Drupal\scheduler_content_moderation_integration\EventSubscriber;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\scheduler\Event\SchedulerEvent;
use Drupal\scheduler\Event\SchedulerMediaEvents;
use Drupal\scheduler\Event\SchedulerNodeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * React to the PUBLISH_IMMEDIATELY scheduler event.
 */
class SchedulerEventSubscriber implements EventSubscriberInterface {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * New instance of SchedulerEventSubscriber.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_information) {
    $this->moderationInformation = $moderation_information;
  }

  /**
   * Operations to perform after Scheduler publishes an entity immediately.
   *
   * This is during the edit process, not via cron.
   *
   * @param \Drupal\scheduler\Event\SchedulerEvent $event
   *   The event being acted on.
   */
  public function publishImmediately(SchedulerEvent $event) {
    /** @var Drupal\Core\Entity\EntityInterface $entity */
    $entity = $event->getNode();

    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    $entity = $event->getEntity();
    $entity->set('moderation_state', $entity->publish_state->getValue());
    $event->setEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The values in the arrays give the function names above. The same function
    // can be used for all supported entity types.
    $events[SchedulerNodeEvents::PUBLISH_IMMEDIATELY][] = ['publishImmediately'];
    $events[SchedulerMediaEvents::PUBLISH_IMMEDIATELY][] = ['publishImmediately'];
    return $events;
  }

}
