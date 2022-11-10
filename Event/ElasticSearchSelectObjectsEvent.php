<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Event;

use Austral\EntityBundle\ORM\AustralQueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Event ElasticSearchSelectObjects.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchSelectObjectsEvent extends Event
{

  const EVENT_QUERY_BUILDER = "austral.elastic_search.select.objects.query_builder";
  const EVENT_OBJECTS = "austral.elastic_search.select.objects";
  const EVENT_OBJECT = "austral.elastic_search.select.object";

  private string $entityClass;

  /**
   * @var array
   */
  private array $objects = array();

  /**
   * @var array
   */
  private array $objectsToHydrate = array();

  /**
   * @var AustralQueryBuilder|null
   */
  private ?AustralQueryBuilder $queryBuilder = null;

  /**
   * ElasticSearchSelectObjectsEvent constructor.
   */
  public function __construct(string $entityClass)
  {
    $this->entityClass = $entityClass;
  }

  /**
   * @return string
   */
  public function getEntityClass(): string
  {
    return $this->entityClass;
  }

  /**
   * @param string $entityClass
   *
   * @return ElasticSearchSelectObjectsEvent
   */
  public function setEntityClass(string $entityClass): ElasticSearchSelectObjectsEvent
  {
    $this->entityClass = $entityClass;
    return $this;
  }

  /**
   * @return array
   */
  public function getObjects(): array
  {
    return $this->objects;
  }

  /**
   * @param array $objects
   *
   * @return ElasticSearchSelectObjectsEvent
   */
  public function setObjects(array $objects): ElasticSearchSelectObjectsEvent
  {
    $this->objects = $objects;
    return $this;
  }

  /**
   * @return array
   */
  public function getObjectsToHydrate(): array
  {
    return $this->objectsToHydrate;
  }

  /**
   * @param array $objectsToHydrate
   *
   * @return ElasticSearchSelectObjectsEvent
   */
  public function setObjectsToHydrate(array $objectsToHydrate): ElasticSearchSelectObjectsEvent
  {
    $this->objectsToHydrate = $objectsToHydrate;
    return $this;
  }

  /**
   * @return AustralQueryBuilder|null
   */
  public function getQueryBuilder(): ?AustralQueryBuilder
  {
    return $this->queryBuilder;
  }

  /**
   * @param AustralQueryBuilder|null $queryBuilder
   *
   * @return ElasticSearchSelectObjectsEvent
   */
  public function setQueryBuilder(?AustralQueryBuilder $queryBuilder): ElasticSearchSelectObjectsEvent
  {
    $this->queryBuilder = $queryBuilder;
    return $this;
  }

}