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
use Austral\ElasticSearchBundle\Event\ElasticSearchHydrateEvent;
use Austral\ElasticSearchBundle\Mapping\ElasticSearchMapping;
use Austral\ElasticSearchBundle\Model\Result;
use Austral\ElasticSearchBundle\Model\Results;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\EntityTranslateBundle\Mapping\EntityTranslateMapping;
use Austral\ManagerBundle\Model\ElasticSearch\DataHydrate;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Traits\IoTrait;
use Doctrine\ORM\QueryBuilder;
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
                "filter" => [ "asciifolding", "lowercase", "3_5_edgegrams"]
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
        $this->client->indices()->create($elasticSearchParameters);
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

    $elasticSearchParameters = $this->elasticSearchParameters();
    /** @var EntityMapping $entityMapping */
    foreach($this->mapping->getEntitiesMapping() as $entityMapping)
    {
      /** @var ElasticSearchMapping $elasticSearchMapping */
      if($elasticSearchMapping = $entityMapping->getEntityClassMapping(ElasticSearchMapping::class))
      {
        $isEntityTranslate = (bool) $entityMapping->getEntityClassMapping(EntityTranslateMapping::class);
        $objects = $entityManager->getRepository($entityMapping->entityClass)->selectByClosure(function(QueryBuilder $queryBuilder) use($isEntityTranslate) {
          if($isEntityTranslate)
          {
            $queryBuilder->leftJoin("root.translates", "translates")->addSelect("translates");
          }
          return $queryBuilder;
        });

        /** @var EntityInterface $object */
        foreach ($objects as $object)
        {
          if($isEntityTranslate)
          {
            foreach ($object->getTranslates() as $translate)
            {
              $object->setCurrentLanguage($translate->getLanguage());
              $this->hydrateObject(
                $elasticSearchParameters,
                $elasticSearchMapping,
                $object,
                sprintf("%s_%s_%s", $object->getSluggerClassname(), $object->getId(), $translate->getLanguage())
              );
            }
          }
          else
          {
            $this->hydrateObject(
              $elasticSearchParameters,
              $elasticSearchMapping,
              $object,
              sprintf("%s_%s", $object->getSluggerClassname(), $object->getId())
            );
          }
        }
      }
    }
    $this->hydratePush($elasticSearchParameters);
    return $this;
  }

  /**
   * @param array $elasticSearchParameters
   * @param ElasticSearchMapping $elasticSearchMapping
   * @param EntityInterface $object
   * @param string $elasticSearchObjectId
   *
   * @return $this
   * @throws \Exception
   */
  protected function hydrateObject(array &$elasticSearchParameters, ElasticSearchMapping $elasticSearchMapping, EntityInterface $object, string $elasticSearchObjectId): ElasticSearch
  {
    $valuesParameters = array();
    if(method_exists($object, "getElasticSearchValues"))
    {
      $elasticSearchValues = $object->getElasticSearchValues();
      if($elasticSearchValues instanceof DataHydrate)
      {
        $valuesParameters = $elasticSearchValues->toArray();
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

    $valuesParameters[Result::VALUE_OBJECT_ID]  = $object->getId();
    $valuesParameters[Result::VALUE_OBJECT_CLASSNAME]  = $object->getClassnameForMapping();

    $objectUpdate = method_exists($object, "getUpdate") ? $object->getUpdate() : new \DateTime() ;
    $valuesParameters[ElasticSearchField::NAME_UPDATE] = $objectUpdate->format("Y-m-d h:i:s");

    $elasticSearchHydrateEvent = new ElasticSearchHydrateEvent($object, array(
      "index" =>  array(
        "_index"  =>  $this->elasticSearchConfiguration->get("index_name"),
        "_type"   =>  $indexType,
        "_id"     =>  $elasticSearchObjectId
      )
    ), $valuesParameters);
    $this->eventDispatcher->dispatch($elasticSearchHydrateEvent, ElasticSearchHydrateEvent::EVENT_HYDRATE);
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
      $isEntityTranslate = (bool) $entityMapping->getEntityClassMapping(EntityTranslateMapping::class);
      if($isEntityTranslate)
      {
        foreach ($object->getTranslates() as $translate)
        {
          $object->setCurrentLanguage($translate->getLanguage());
          $this->hydrateObject(
            $elasticSearchParameters,
            $elasticSearchMapping,
            $object,
            sprintf("%s_%s_%s", $object->getSluggerClassname(), $object->getId(), $translate->getLanguage())
          );
          dump($elasticSearchParameters);
        }
      }
      else
      {
        $this->hydrateObject(
          $elasticSearchParameters,
          $elasticSearchMapping,
          $object,
          sprintf("%s_%s", $object->getSluggerClassname(), $object->getId())
        );
      }
      dump($elasticSearchParameters);
      dump("toto");
      $this->hydratePush($elasticSearchParameters);
    }



    return $this;
  }

  /**
   * @param array $elasticSearchParameters
   *
   * @return $this
   */
  protected function hydratePush(array $elasticSearchParameters = array()): ElasticSearch
  {
    $reponse = null;
    try {
      if(count($elasticSearchParameters) > 0)
      {
        $reponse = $this->client->bulk($elasticSearchParameters);
        if($reponse["errors"])
        {
          throw new \Exception("Hydrate is failed");
        }

        $nbItemCreated = 0;
        $nbItemUpdated = 0;
        $nbItems = count($reponse['items']);
        foreach($reponse['items'] as $item)
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
      AustralTools::dump($reponse, $e);
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
   *
   * @return Results
   * @throws \Exception
   */
  public function searchByQuery(string $query, int $offset = 0, int $limit = 20, ?string $language = null, ?string $keyname = null): Results
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
      $filters["filter"] = array(
        "term"    =>  array(
          "language"  =>  $language
        )
      );
    }
    return $this->searchByFiltres($filters, $offset, $limit, $keyname);
  }

  /**
   * @param array $filtres
   * @param int $offset
   * @param int $limit
   * @param string|null $keyname
   *
   * @return Results
   * @throws \Exception
   */
  public function searchByFiltres(array $filtres = array(), int $offset = 0, int $limit = 20, ?string $keyname = null): Results
  {
    $parameters = array(
      "index"  => $this->elasticSearchConfiguration->get("index_name"),
      "from"   => $offset,
      "size"   => $limit,
      "body"   =>  array(
        "query"   =>  $filtres,
        "highlight" => array(
          "pre_tags" => array("<span class='highlight-search'><span class='value'>"),
          "post_tags" => array("</span></span>"),
          "fields" => array(
            ElasticSearchField::NAME_TITLE  => array("force_source" => true, "fragment_size" => 300, "no_match_size" => 300, "number_of_fragments" => 1),
            ElasticSearchField::NAME_REF_H1  => array("force_source" => true, "fragment_size" => 300, "no_match_size" => 300, "number_of_fragments" => 1),
            ElasticSearchField::NAME_REF_TITLE  => array("force_source" => true, "fragment_size" => 300, "no_match_size" => 300, "number_of_fragments" => 1),
            ElasticSearchField::NAME_CONTENT  => array("force_source" => true, "fragment_size" => 90, "number_of_fragments" => 3, "no_match_size" => 270)
          )
        )
      )
    );
    $elasticSearchEvent = new ElasticSearchEvent($this->elasticSearchConfiguration->get("index_name"), $parameters, $keyname);
    $this->eventDispatcher->dispatch($elasticSearchEvent, ElasticSearchEvent::EVENT_FILTER);

    $results = $this->client->search($parameters);
    $searchResult = new Results();
    $searchResult->initValues($results);
    return $searchResult;
  }

}