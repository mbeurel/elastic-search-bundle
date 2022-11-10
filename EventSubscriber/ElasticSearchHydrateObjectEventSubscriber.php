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

use Austral\ElasticSearchBundle\Event\ElasticSearchHydrateObjectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Austral ElasticSearchHydrateObject EventSubscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchHydrateObjectEventSubscriber implements EventSubscriberInterface
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
      ElasticSearchHydrateObjectEvent::EVENT_HYDRATE     =>  ["hydrate", 0]
    ];
  }

  /**
   * @param ElasticSearchHydrateObjectEvent $elasticSearchHydrateEvent
   *
   * @return void
   */
  public function hydrate(ElasticSearchHydrateObjectEvent $elasticSearchHydrateEvent)
  {
  }

}