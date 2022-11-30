<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Listener;

use Austral\ElasticSearchBundle\Configuration\ElasticSearchConfiguration;
use Austral\ElasticSearchBundle\Services\ElasticSearch;
use Austral\EntityBundle\Entity\Interfaces\TranslateChildInterface;
use Austral\ToolsBundle\AustralTools;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

/**
 * Austral Doctrine Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DoctrineListener implements EventSubscriber
{

  /**
   * @var mixed
   */
  protected $name;

  /**
   * @var ElasticSearchConfiguration
   */
  protected ElasticSearchConfiguration $elasticSearchConfiguration;

  /**
   * @var ElasticSearch
   */
  protected ElasticSearch $elasticSearch;


  /**
   * DoctrineListener constructor.
   */
  public function __construct(ElasticSearch $elasticSearch, ElasticSearchConfiguration $elasticSearchConfiguration)
  {
    $this->elasticSearch = $elasticSearch;
    $this->elasticSearchConfiguration = $elasticSearchConfiguration;
    $parts = explode('\\', $this->getNamespace());
    $this->name = end($parts);
  }

  /**
   * @return string[]
   */
  public function getSubscribedEvents(): array
  {
      return array(
        Events::preRemove,
        Events::postUpdate,
        Events::postPersist
      );
  }

  /**
   * @param LifecycleEventArgs $args
   *
   * @throws \Exception
   */
  public function postUpdate(LifecycleEventArgs $args): void
  {
    if($this->elasticSearchConfiguration->get("enabled"))
    {
      $ea = $this->getEventAdapter($args);
      $object = $ea->getObject();
      if($object instanceof TranslateChildInterface)
      {
        $object = $object->getMaster();
      }
      $this->elasticSearch->createOrUpdateObject($object);
    }
  }

  /**
   * @param LifecycleEventArgs $args
   *
   * @throws \Exception
   */
  public function postPersist(LifecycleEventArgs $args): void
  {
    if($this->elasticSearchConfiguration->get("enabled"))
    {
      $ea = $this->getEventAdapter($args);
      $object = $ea->getObject();
      if($object instanceof TranslateChildInterface)
      {
        $object = $object->getMaster();
      }
      $this->elasticSearch->createOrUpdateObject($object);
    }
  }

  /**
   * @param LifecycleEventArgs $args
   *
   * @throws \Exception
   */
  public function preRemove(LifecycleEventArgs $args): void
  {
    if($this->elasticSearchConfiguration->get("enabled"))
    {
      $ea = $this->getEventAdapter($args);
      $object = $ea->getObject();
      if($object instanceof TranslateChildInterface)
      {
        $object = $object->getMaster();
      }
      $this->elasticSearch->deleteObject($object);
    }
  }

  /**
   * @param EventArgs $args
   *
   * @return EventArgs
   */
  protected function getEventAdapter(EventArgs $args)
  {
    return $args;
  }

  /**
   * @return string
   */
  protected function getNamespace()
  {
    return __NAMESPACE__;
  }
}