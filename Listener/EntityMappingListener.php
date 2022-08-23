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

use Austral\ElasticSearchBundle\Annotation\ElasticSearch;
use Austral\ElasticSearchBundle\Annotation\ElasticSearchField;
use Austral\ElasticSearchBundle\Mapping\ElasticSearchMapping;
use Austral\EntityBundle\Annotation\AustralEntityAnnotationInterface;
use Austral\EntityBundle\EntityAnnotation\EntityAnnotations;
use Austral\EntityBundle\Event\EntityMappingEvent;
use Austral\EntityBundle\Mapping\EntityMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Austral EntityMapping Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class EntityMappingListener
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @param ContainerInterface $container
   */
  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
  }


  /**
   * @param EntityMappingEvent $entityAnnotationEvent
   *
   * @return void
   * @throws \Exception
   */
  public function mapping(EntityMappingEvent $entityAnnotationEvent)
  {
    $initialiseEntitesAnnotations = $entityAnnotationEvent->getEntitiesAnnotations();

    /**
     * @var EntityAnnotations $entityAnnotation
     */
    foreach($initialiseEntitesAnnotations->all() as $entityAnnotation)
    {
      if(array_key_exists(ElasticSearch::class, $entityAnnotation->getClassAnnotations()))
      {
        if(!$entityMapping = $entityAnnotationEvent->getMapping()->getEntityMapping($entityAnnotation->getClassname()))
        {
          $entityMapping = new EntityMapping($entityAnnotation->getClassname(), $entityAnnotation->getSlugger());
        }

        $elasticSearchMapping = new ElasticSearchMapping();

        foreach($entityAnnotation->getClassAnnotations()[ElasticSearch::class]->fieldAnnotations as $fieldAnnotation)
        {
          if($fieldAnnotation instanceof ElasticSearchField)
          {
            $elasticSearchMapping->addFieldAnnotation($fieldAnnotation);
          }
        }

        /** @var array $fieldsAnnotation */
        foreach($entityAnnotation->getFieldsAnnotations() as $fieldsAnnotation)
        {
          /** @var AustralEntityAnnotationInterface $fieldAnnotation */
          foreach($fieldsAnnotation as $classFieldAnnotation => $fieldAnnotation)
          {
            if($classFieldAnnotation === ElasticSearchField::class)
            {
              $elasticSearchMapping->addFieldAnnotation($fieldAnnotation);
            }
          }
        }
        $entityMapping->addEntityClassMapping($elasticSearchMapping);
        $entityAnnotationEvent->getMapping()->addEntityMapping($entityAnnotation->getClassname(), $entityMapping);
      }
    }
  }

}
