<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Model;

use Austral\EntityBundle\Entity\EntityInterface;

/**
 * Austral ElasticSearch ObjectToHydrate Model.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ObjectToHydrate
{

  /**
   * @var string
   */
  protected string $elasticSearchId;

  /**
   * @var EntityInterface
   */
  protected EntityInterface $object;

  /**
   * @var array
   */
  protected array $valuesParameters = array();

  /**
   * @param string $elasticSearchId
   * @param EntityInterface $object
   *
   * @return ObjectToHydrate
   */
  public static function create(string $elasticSearchId, EntityInterface $object): ObjectToHydrate
  {
    return new self($elasticSearchId, $object);
  }

  /**
   * DataHydrate constructor.
   */
  public function __construct(string $elasticSearchId, EntityInterface $object)
  {
    $this->elasticSearchId = $elasticSearchId;
    $this->object = $object;
  }

  /**
   * @return string
   */
  public function getElasticSearchId(): string
  {
    return $this->elasticSearchId;
  }

  /**
   * @param string $elasticSearchId
   *
   * @return ObjectToHydrate
   */
  public function setElasticSearchId(string $elasticSearchId): ObjectToHydrate
  {
    $this->elasticSearchId = $elasticSearchId;
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
   * @return ObjectToHydrate
   */
  public function setObject(EntityInterface $object): ObjectToHydrate
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
   * @param string $valueKey
   * @param $valuesParameter
   *
   * @return ObjectToHydrate
   */
  public function addValuesParameters(string $valueKey, $valuesParameter): ObjectToHydrate
  {
    $this->valuesParameters[$valueKey] = $valuesParameter;
    return $this;
  }

  /**
   * @param array $valuesParameters
   *
   * @return ObjectToHydrate
   */
  public function setValuesParameters(array $valuesParameters): ObjectToHydrate
  {
    $this->valuesParameters = $valuesParameters;
    return $this;
  }

}