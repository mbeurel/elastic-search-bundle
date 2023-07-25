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

use Austral\EntityBundle\Entity\EntityInterface;
use Austral\ToolsBundle\AustralTools;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Event ElasticSearchHydrateObject.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchHydrateObjectEvent extends Event
{

  const EVENT_HYDRATE = "austral.elastic_search.hydrate.object";

  /**
   * @var EntityInterface
   */
  private EntityInterface $object;

  /**
   * @var array
   */
  private array $indexParameters;

  /**
   * @var array
   */
  private array $valuesParameters;


  /**
   * ElasticSearchHydrateObjectEvent constructor.
   *
   * @param EntityInterface $object
   * @param array $indexParameters
   * @param array $valuesParameters
   */
  public function __construct(EntityInterface $object, array $indexParameters = array(), array $valuesParameters = array())
  {
    $this->object = $object;
    $this->indexParameters = $indexParameters;
    $this->valuesParameters = $valuesParameters;
  }

  /**
   * @return array
   */
  public function getIndexParameters(): array
  {
    return $this->indexParameters;
  }

  /**
   * @param array $indexParameters
   *
   * @return ElasticSearchHydrateObjectEvent
   */
  public function setIndexParameters(array $indexParameters): ElasticSearchHydrateObjectEvent
  {
    $this->indexParameters = $indexParameters;
    return $this;
  }

  /**
   * @return EntityInterface
   */
  public function getObject(): EntityInterface
  {
    return $this->object;
  }

  /**
   * @param EntityInterface $object
   *
   * @return ElasticSearchHydrateObjectEvent
   */
  public function setObject(EntityInterface $object): ElasticSearchHydrateObjectEvent
  {
    $this->object = $object;
    return $this;
  }

  /**
   * @return array
   */
  public function getValuesParameters(): array
  {
    return $this->valuesParameters;
  }

  /**
   * @param array $valuesParameters
   *
   * @return ElasticSearchHydrateObjectEvent
   */
  public function setValuesParameters(array $valuesParameters = array()): ElasticSearchHydrateObjectEvent
  {
    $this->valuesParameters = $valuesParameters;
    return $this;
  }

  /**
   * @param string $key
   * @param $value
   * @return ElasticSearchHydrateObjectEvent
   */
  public function addValuesParameter(string $key, $value): ElasticSearchHydrateObjectEvent
  {
    $this->valuesParameters[$key] = $value;
    return $this;
  }

  /**
   * @param string $key
   * @param null $default
   * @return mixed
   */
  public function getValueParameterByKey(string $key, $default = null)
  {
    return AustralTools::getValueByKey($this->getValuesParameters(), $key, $default);
  }

}