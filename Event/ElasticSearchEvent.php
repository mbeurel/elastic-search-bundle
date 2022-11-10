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

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Event ElasticSearch.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchEvent extends Event
{

  const EVENT_CREATE_INDEX = "austral.elastic_search.create";
  const EVENT_FILTER = "austral.elastic_search.filter";

  /**
   * @var string
   */
  private string $indexName;

  /**
   * @var string|null
   */
  private ?string $keyname;

  /**
   * @var array
   */
  private array $elasticSearchParameters;

  /**
   * @param string $indexName
   * @param array $elasticSearchParameters
   * @param string|null $keyname
   */
  public function __construct(string $indexName, array $elasticSearchParameters = array(), ?string $keyname = null)
  {
    $this->indexName = $indexName;
    $this->elasticSearchParameters = $elasticSearchParameters;
    $this->keyname = $keyname;
  }

  /**
   * @return string
   */
  public function getIndexName(): string
  {
    return $this->indexName;
  }

  /**
   * @return string|null
   */
  public function getKeyname(): ?string
  {
    return $this->keyname;
  }

  /**
   * @return array
   */
  public function getElasticSearchParameters(): array
  {
    return $this->elasticSearchParameters;
  }

  /**
   * @param array $elasticSearchParameters
   *
   * @return ElasticSearchEvent
   */
  public function setElasticSearchParameters(array $elasticSearchParameters): ElasticSearchEvent
  {
    $this->elasticSearchParameters = $elasticSearchParameters;
    return $this;
  }

}