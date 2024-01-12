<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Services;

use Austral\ElasticSearchBundle\Annotation\ElasticSearchField;
use Austral\ElasticSearchBundle\Configuration\ElasticSearchConfiguration;
use Austral\ElasticSearchBundle\Event\ElasticSearchEvent;
use Austral\ElasticSearchBundle\Event\ElasticSearchHydrateObjectEvent;
use Austral\ElasticSearchBundle\Event\ElasticSearchSelectObjectsEvent;
use Austral\ElasticSearchBundle\Mapping\ElasticSearchMapping;
use Austral\ElasticSearchBundle\Model\ObjectToHydrate;
use Austral\ElasticSearchBundle\Model\Result;
use Austral\ElasticSearchBundle\Model\Results;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityBundle\ORM\AustralQueryBuilder;
use Austral\EntityTranslateBundle\Mapping\EntityTranslateMapping;
use Austral\ElasticSearchBundle\Model\DataHydrate;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Traits\IoTrait;
use Elasticsearch\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Elasticsearch\ClientBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Austral ElasticSearch Services.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class ElasticSearch
{

  use IoTrait;

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * @var EntityManager
   */
  protected EntityManager $entityManager;

  /**
   * @var ElasticSearchConfiguration
   */
  protected ElasticSearchConfiguration $elasticSearchConfiguration;

  /**
   * @var Client
   */
  protected Client $client;

  /**
   * @var Mapping
   */
  protected Mapping $mapping;


  /**
   * @param ContainerInterface $container
   * @param EventDispatcherInterface $eventDispatcher
   * @param EntityManager $entityManager
   * @param ElasticSearchConfiguration $elasticSearchConfiguration
   * @param Mapping $mapping
   */
  public function __construct(ContainerInterface $container, EventDispatcherInterface $eventDispatcher, EntityManager $entityManager, ElasticSearchConfiguration $elasticSearchConfiguration, Mapping $mapping)
  {
    $this->container = $container;
    $this->eventDispatcher = $eventDispatcher;
    $this->elasticSearchConfiguration = $elasticSearchConfiguration;
    $this->entityManager = $entityManager;
    $clientBuilder = ClientBuilder::create()->setHosts($this->elasticSearchConfiguration->get("hosts"));
    if($this->elasticSearchConfiguration->get("logger.enabled"))
    {
      $logger    = new Logger('log');
      $handler   = new StreamHandler(sprintf("%s/elastic-search_%s.log", $this->container->getParameter("kernel.logs_dir"), $this->container->getParameter("kernel.debug") ? "dev" : "prod"), Logger::DEBUG);
      $logger->pushHandler($handler);
      $clientBuilder->setLogger($logger);
    }
    $this->client = $clientBuilder->build();
    $this->mapping = $mapping;
  }

  /**
   * getClient
   * @return Client
   */
  public function getClient(): Client
  {
    return $this->client;
  }

  /**
   * @return array
   */
  protected function elasticSearchParameters(): array
  {
    return array(
      "index"  =>  $this->elasticSearchConfiguration->get("index_name")
    );
  }

  /**
   * @return $this
   */
  public function dropIndex(): ElasticSearch
  {
    if($this->client->indices()->exists($this->elasticSearchParameters()))
    {
      try {
        $this->client->indices()->delete($this->elasticSearchParameters());
        $this->viewMessage("Elastic Search - Drop index success");
      } catch(\Exception $e) {
        $this->viewMessage("Elastic Search - Drop index error : {$e->getMessage()}", "error");
      }
    }
    return $this;
  }

  /**
   * @return $this
   */
  public function createIndex(): ElasticSearch
  {
    $elasticSearchParameters = $this->elasticSearchParameters();
    if(!$this->client->indices()->exists($elasticSearchParameters))
    {
      $elasticSearchParameters["body"] = array(
        "settings"  =>  array(
          "analysis"=>  array(
            "analyzer" => array(
              "default" =>  array(
                "tokenizer" =>  "standard",
                "filter"    => [ "asciifolding", "lowercase", "3_5_edgegrams"]
              )
            ),
            "filter" => array(
              "3_5_edgegrams" => array(
                "type" => "edge_ngram",
                "min_gram" => 3,
                "max_gram" => 5
              )
            )
          )
        )
      );
      $elasticSearchEvent = new ElasticSearchEvent($this->elasticSearchConfiguration->get("index_name"), $elasticSearchParameters);
      $this->eventDispatcher->dispatch($elasticSearchEvent, ElasticSearchEvent::EVENT_CREATE_INDEX);
      try {
        $this->client->indices()->create($elasticSearchEvent->getElasticSearchParameters());
        $this->viewMessage("Elastic Search - Create index success");
      } catch(\Exception $e) {
        $this->viewMessage("Elastic Search - Create index error : {$e->getMessage()}", "error");
      }
    }
    return $this;
  }

  /**
   * @return ElasticSearch
   * @throws \Exception
   */
  public function hydrate(): ElasticSearch
  {
    /** @var EntityManager $entityManager */
    $entityManager = $this->container->get('austral.entity_manager');


    /** @var EntityMapping $entityMapping */
    foreach($this->mapping->getEntitiesMapping() as $entityMapping)
    {
      /** @var ElasticSearchMapping $elasticSearchMapping */
      if($elasticSearchMapping = $entityMapping->getEntityClassMapping(ElasticSearchMapping::class))
      {

        $elasticSearchMappingProperties = array();
        /** @var ElasticSearchField $fieldAnnotation */
        foreach ($elasticSearchMapping->getFieldsAnnotations() as $fieldAnnotation)
        {
          if($fieldAnnotation->mappingType)
          {
            $elasticSearchMappingProperties[$fieldAnnotation->name] = array(
              "type"  => $fieldAnnotation->mappingType
            );
          }
        }
        if($elasticSearchMappingProperties)
        {
          $this->client->indices()->putMapping(array(
            "index" =>   $this->elasticSearchConfiguration->get("index_name"),
            "body"      =>  array(
              "properties"  =>  $elasticSearchMappingProperties
            )
          ));
        }

        $eventDispatcher = $this->eventDispatcher;
        $eventQuery = new ElasticSearchSelectObjectsEvent($entityMapping->entityClass);

        $countObject = $entityManager->getRepository($entityMapping->entityClass)->countAll(function(AustralQueryBuilder $queryBuilder) use($eventQuery, $eventDispatcher){
          $eventQuery->setQueryBuilder($queryBuilder);
          $eventQuery->setIsCount(true);
          $eventDispatcher->dispatch($eventQuery, ElasticSearchSelectObjectsEvent::EVENT_QUERY_BUILDER);
          return $queryBuilder;
        });

        $offset = 0;
        while ($offset != $countObject)
        {
          $eventQuery = new ElasticSearchSelectObjectsEvent($entityMapping->entityClass);
          $elasticSearchParametersToObject = $this->elasticSearchParameters();
          $entityManager->clear();
          $objects = $entityManager->getRepository($entityMapping->entityClass)->selectByClosure(function(AustralQueryBuilder $queryBuilder) use($eventQuery, $eventDispatcher, $offset){
            $eventQuery->setQueryBuilder($queryBuilder);
            $eventDispatcher->dispatch($eventQuery, ElasticSearchSelectObjectsEvent::EVENT_QUERY_BUILDER);
            $queryBuilder->setMaxResults(1000)
              ->setFirstResult($offset);
            return $queryBuilder;
          });
          $offset += count($objects);
          $this->viewMessage("Elastic Search - Objects {$offset} / {$countObject}");

          $eventQuery->setObjects($objects);
          $eventDispatcher->dispatch($eventQuery, ElasticSearchSelectObjectsEvent::EVENT_OBJECTS);

          /** @var ObjectToHydrate $objectToHydrate */
          foreach ($eventQuery->getObjectsToHydrate() as $objectToHydrate)
          {
            $this->hydrateObject(
              $elasticSearchParametersToObject,
              $elasticSearchMapping,
              $objectToHydrate->getObject(),
              $objectToHydrate->getElasticSearchId(),
              $objectToHydrate->getValuesParameters()
            );
          }
          $this->hydratePush($elasticSearchParametersToObject);
        }

      }
    }
    return $this;
  }

  /**
   * @param array $elasticSearchParameters
   * @param ElasticSearchMapping $elasticSearchMapping
   * @param EntityInterface $object
   * @param string $elasticSearchObjectId
   * @param array $valuesParameters
   *
   * @return $this
   * @throws \Exception
   */
  protected function hydrateObject(
    array &$elasticSearchParameters,
    ElasticSearchMapping $elasticSearchMapping,
    EntityInterface $object,
    string $elasticSearchObjectId,
    array $valuesParameters = array()
  ): ElasticSearch
  {
    if(method_exists($object, "getElasticSearchValues"))
    {
      $elasticSearchValues = $object->getElasticSearchValues();
      if($elasticSearchValues instanceof DataHydrate)
      {
        $valuesParameters = array_merge($valuesParameters, $elasticSearchValues->toArray());
      }
    }

    $indexType = "_doc";
    /** @var ElasticSearchField $fieldAnnotation */
    foreach ($elasticSearchMapping->getFieldsAnnotations() as $fieldAnnotation)
    {
      if($fieldAnnotation->name === ElasticSearchField::INDEX_TYPE)
      {
        $indexType = $fieldAnnotation->value ?? $indexType;
      }
      $valuesParameters[$fieldAnnotation->name] = $fieldAnnotation->value ?? $elasticSearchMapping->getObjectValue($object, $fieldAnnotation->keyname);
    }

    $valuesParameters[Result::OBJECT_NAME] = $elasticSearchMapping->getName();
    $valuesParameters[Result::VALUE_OBJECT_ID]  = $object->getId();
    $valuesParameters[Result::VALUE_OBJECT_CLASSNAME]  = $object->getClassnameForMapping();

    $objectUpdate = method_exists($object, "getUpdate") ? $object->getUpdate() : new \DateTime() ;
    $valuesParameters[ElasticSearchField::NAME_UPDATE] = $objectUpdate->getTimestamp();

    $elasticSearchHydrateEvent = new ElasticSearchHydrateObjectEvent($object, array(
      "index" =>  array(
        "_index"  =>  $this->elasticSearchConfiguration->get("index_name"),
        "_type"   =>  $indexType,
        "_id"     =>  $elasticSearchObjectId
      )
    ), $valuesParameters);

    $this->eventDispatcher->dispatch($elasticSearchHydrateEvent, ElasticSearchHydrateObjectEvent::EVENT_HYDRATE);
    $elasticSearchParameters['body'][] = $elasticSearchHydrateEvent->getIndexParameters();
    $elasticSearchParameters['body'][] = $elasticSearchHydrateEvent->getValuesParameters();
    return $this;
  }

  /**
   * @param EntityInterface $object
   *
   * @return ElasticSearch
   * @throws \Exception
   */
  public function createOrUpdateObject(EntityInterface $object): ElasticSearch
  {
    /** @var EntityMapping $entityMapping */
    $entityMapping = $this->mapping->getEntityMapping($object->getClassnameForMapping());

    $elasticSearchParameters = $this->elasticSearchParameters();
    /** @var ElasticSearchMapping $elasticSearchMapping */
    if($entityMapping && ($elasticSearchMapping = $entityMapping->getEntityClassMapping(ElasticSearchMapping::class)))
    {

      $eventQuery = new ElasticSearchSelectObjectsEvent($entityMapping->entityClass);

      $eventQuery->setObjects(array($object));
      $this->eventDispatcher->dispatch($eventQuery, ElasticSearchSelectObjectsEvent::EVENT_OBJECTS);

      /** @var ObjectToHydrate $objectToHydrate */
      foreach ($eventQuery->getObjectsToHydrate() as $objectToHydrate)
      {
        $this->hydrateObject(
          $elasticSearchParameters,
          $elasticSearchMapping,
          $objectToHydrate->getObject(),
          $objectToHydrate->getElasticSearchId(),
          $objectToHydrate->getValuesParameters()
        );
      }
      $this->hydratePush($elasticSearchParameters);
    }
    return $this;
  }

  /**
   * @param array $elasticSearchParameters
   * @return $this
   */
  protected function hydratePush(array $elasticSearchParameters = array()): ElasticSearch
  {
    $response = null;
    try {
      if(count($elasticSearchParameters) > 0)
      {
        $response = $this->client->bulk($elasticSearchParameters);
        if($response["errors"])
        {
          throw new \Exception("Hydrate is failed -> {$response["errors"]}");
        }

        $nbItemCreated = 0;
        $nbItemUpdated = 0;
        $nbItems = count($response['items']);
        foreach($response['items'] as $item)
        {
          if($item["index"]["result"] === "created")
          {
            $nbItemCreated++;
          }
          elseif($item["index"]["result"] === "updated")
          {
            $nbItemUpdated++;
          }
        }
        $this->viewMessage("Elastic Search - Hydrate success -> {$nbItems} item(s) / {$nbItemCreated} item(s) created / {$nbItemUpdated} item(s) updated");
      }
      else
      {
        $this->viewMessage("Elastic Search - Hydrate success -> 0 item");
      }
    } catch(\Exception $e) {
      AustralTools::dump($response, $e);
      $this->viewMessage("Elastic Search - Hydrate error : {$e->getMessage()}", "error");
    }
    return $this;
  }

  /**
   * @param EntityInterface $object
   *
   * @return ElasticSearch
   */
  public function deleteObject(EntityInterface $object): ElasticSearch
  {
    /** @var EntityMapping $entityMapping */
    $entityMapping = $this->mapping->getEntityMapping($object->getClassnameForMapping());

    if($entityMapping && $entityMapping->getEntityClassMapping(ElasticSearchMapping::class))
    {
      $isEntityTranslate = (bool) $entityMapping->getEntityClassMapping(EntityTranslateMapping::class);
      $elasticSearchParameters = array();
      if($isEntityTranslate)
      {
        foreach($object->getTranslates() as $translate)
        {
          $elasticSearchParameters[] = array(
            "index"  => $this->elasticSearchConfiguration->get("index_name"),
            "id"     => sprintf("%s_%s_%s", $object->getSluggerClassname(), $object->getId(), $translate->getLanguage()),
          );
        }
      }
      else
      {
        $elasticSearchParameters[] = array(
          "index"  => $this->elasticSearchConfiguration->get("index_name"),
          "id"     => sprintf("%s_%s", $object->getSluggerClassname(), $object->getId()),
        );
      }

      try {
        $this->client->indices()->create($elasticSearchParameters);
        $this->viewMessage("Elastic Search - Create index success");
      } catch(\Exception $e) {
      }

      $nbItemsDeleted = 0;
      $nbItems = count($elasticSearchParameters);
      try {
        foreach($elasticSearchParameters as $parametersIndex)
        {
          $this->client->delete($parametersIndex);
          $nbItemsDeleted++;
        }
        $this->viewMessage("Elastic Search - Delete success -> {$nbItemsDeleted}/{$nbItems} item(s) deleted");
      }
      catch(\Exception $e )
      {
        $this->viewMessage("Elastic Search - Delete error : {$e->getMessage()}", "error");
      }
    }
    return $this;
  }

  /**
   * @param string $query
   * @param int $offset
   * @param int $limit
   * @param string|null $language
   * @param string|null $keyname
   * @param array $sort
   * @param array $body
   *
   * @return Results
   * @throws \Exception
   */
  public function searchByQuery(string $query, int $offset = 0, int $limit = 20, ?string $language = null, ?string $keyname = null, array $sort = array(), array $body = array()): Results
  {
    $filters = array(
      "bool"    =>  array(
        "must"    =>  array(
          "query_string"  =>  array(
            "query"   =>  "({$query})",
            "default_operator"  =>  "AND"
          )
        ),
      )
    );

    if($language)
    {
      $filters["bool"]["filter"] = array(
        "match"    =>  array(
          "language"  =>  $language
        )
      );
    }
    return $this->searchByFiltres($filters, $offset, $limit, $keyname, $sort, $body);
  }

  /**
   * @param array $filtres
   * @param int $offset
   * @param int $limit
   * @param string|null $keyname
   * @param array $sort
   * @param array $body
   *
   * @return Results
   * @throws \Exception
   */
  public function searchByFiltres(array $filtres = array(), int $offset = 0, int $limit = 20, ?string $keyname = null, array $sort = array(), array $body = array()): Results
  {
    $count = $this->countByFilter($filtres, $keyname, $body);
    if(!array_key_exists("highlight", $body))
    {
      $body["highlight"] = array(
        "pre_tags" => array("<span class='highlight-search'><span class='value'>"),
        "post_tags" => array("</span></span>"),
        "fields" => array(
          ElasticSearchField::NAME_TITLE  => array("force_source" => true, "fragment_size" => 300, "no_match_size" => 300, "number_of_fragments" => 1),
          ElasticSearchField::NAME_REF_H1  => array("force_source" => true, "fragment_size" => 300, "no_match_size" => 300, "number_of_fragments" => 1),
          ElasticSearchField::NAME_REF_TITLE  => array("force_source" => true, "fragment_size" => 300, "no_match_size" => 300, "number_of_fragments" => 1),
          ElasticSearchField::NAME_CONTENT  => array("force_source" => true, "fragment_size" => 90, "number_of_fragments" => 3, "no_match_size" => 270)
        )
      );
    }

    if($filtres)
    {
      $body["query"] = $filtres;
    }

    if($sort)
    {
      $body["sort"] = $sort;
    }

    $parameters = array(
      "index"  => $this->elasticSearchConfiguration->get("index_name"),
      "from"   => $offset,
      "size"   => $limit,
      "body"   =>  $body
    );

    $elasticSearchEvent = new ElasticSearchEvent($this->elasticSearchConfiguration->get("index_name"), $parameters, $keyname);
    $this->eventDispatcher->dispatch($elasticSearchEvent, ElasticSearchEvent::EVENT_FILTER);

    $results = $this->client->search($elasticSearchEvent->getElasticSearchParameters());

    $searchResult = new Results();
    $searchResult->initValues($results);

    $searchResult->setNbResults($count);
    return $searchResult;
  }

  /**
   * @param array $filtres
   * @param string|null $keyname
   * @param array $body
   *
   * @return int
   * @throws \Exception
   */
  public function countByFilter(array $filtres = array(), ?string $keyname = null, array $body = array()): int
  {
    if($filtres)
    {
      $body["query"] = $filtres;
    }
    if(array_key_exists("highlight", $body))
    {
      unset($body["highlight"]);
    }
    $parameters = array(
      "index"  => $this->elasticSearchConfiguration->get("index_name"),
      "body"   =>  $body
    );
    $elasticSearchEvent = new ElasticSearchEvent($this->elasticSearchConfiguration->get("index_name"), $parameters, $keyname);
    $this->eventDispatcher->dispatch($elasticSearchEvent, ElasticSearchEvent::EVENT_FILTER);
    $countResponse = $this->client->count($elasticSearchEvent->getElasticSearchParameters());
    return $countResponse["count"];
  }


}