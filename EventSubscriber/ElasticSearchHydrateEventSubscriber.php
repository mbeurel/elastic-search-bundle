<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\EventSubscriber;

use Austral\ElasticSearchBundle\Event\ElasticSearchEvent;
use Austral\ElasticSearchBundle\Event\ElasticSearchHydrateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Austral ElasticSearchHydrate EventSubscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchHydrateEventSubscriber implements EventSubscriberInterface
{

  /**
   * ControllerListener constructor.
   *
   */
  public function __construct()
  {
  }


  /**
   * @return array[]
   */
  public static function getSubscribedEvents(): array
  {
    return [
      ElasticSearchHydrateEvent::EVENT_HYDRATE     =>  ["hydrate", 1024]
    ];
  }

  /**
   * @param ElasticSearchHydrateEvent $elasticSearchHydrateEvent
   *
   * @return void
   */
  public function hydrate(ElasticSearchHydrateEvent $elasticSearchHydrateEvent)
  {
  }

}