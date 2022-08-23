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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Austral ElasticSearch EventSubscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchEventSubscriber implements EventSubscriberInterface
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
      ElasticSearchEvent::EVENT_CREATE_INDEX     =>  ["createIndex", 1024]
    ];
  }

  /**
   * @param ElasticSearchEvent $elasticSearchEvent
   *
   * @return void
   */
  public function createIndex(ElasticSearchEvent $elasticSearchEvent)
  {
  }

}