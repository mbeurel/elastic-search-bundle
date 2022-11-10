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

use Austral\ElasticSearchBundle\Event\ElasticSearchSelectObjectsEvent;
use Austral\ElasticSearchBundle\Model\ObjectToHydrate;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Austral ElasticSearchSelectObjects EventSubscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchSelectObjectsEventSubscriber implements EventSubscriberInterface
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
      ElasticSearchSelectObjectsEvent::EVENT_OBJECTS     =>  ["objects", 1024]
    ];
  }

  /**
   * @param ElasticSearchSelectObjectsEvent $elasticSearchSelectObjectsEvent
   *
   * @return void
   */
  public function objects(ElasticSearchSelectObjectsEvent $elasticSearchSelectObjectsEvent)
  {
    if(!$elasticSearchSelectObjectsEvent->getObjectsToHydrate())
    {
      $objectsToHydrate = array();
      foreach ($elasticSearchSelectObjectsEvent->getObjects() as $object)
      {
        $objectToHydrate = new ObjectToHydrate(sprintf("%s_%s", $object->getSluggerClassname(), $object->getId()), $object);
        $objectsToHydrate[$objectToHydrate->getElasticSearchId()] = $objectToHydrate;
      }
      $elasticSearchSelectObjectsEvent->setObjectsToHydrate($objectsToHydrate);
    }
  }

}