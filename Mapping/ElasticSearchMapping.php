<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Mapping;

use Austral\ElasticSearchBundle\Annotation\ElasticSearchField;
use Austral\EntityBundle\Annotation\AustralEntityAnnotationInterface;
use Austral\EntityBundle\Mapping\EntityClassMapping;

/**
 * Austral ElasticSearchMapping.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
final Class ElasticSearchMapping extends EntityClassMapping
{

  /**
   * @var array
   */
  protected array $fieldsAnnotations = array();

  /**
   * @var string|null
   */
  protected ?string $name = null;


  /**
   * Constructor.
   */
  public function __construct(?string $name = null)
  {
    $this->name = $name;
  }

  /**
   * @param AustralEntityAnnotationInterface $entityAnnotation
   *
   * @return ElasticSearchMapping
   */
  public function addFieldAnnotation(AustralEntityAnnotationInterface $entityAnnotation): ElasticSearchMapping
  {
    if($entityAnnotation->getKeyname() === ElasticSearchField::NAME_EXTRA_CONTENT)
    {
      if(!array_key_exists($entityAnnotation->getKeyname(), $this->fieldsAnnotations))
      {
        $this->fieldsAnnotations[$entityAnnotation->getKeyname()] = array();
      }
      $this->fieldsAnnotations[$entityAnnotation->getKeyname()][] = $entityAnnotation;
    }
    else
    {
      $this->fieldsAnnotations[$entityAnnotation->getKeyname()] = $entityAnnotation;
    }
    return $this;
  }

  /**
   * @return array
   */
  public function getFieldsAnnotations(): array
  {
    return $this->fieldsAnnotations;
  }

  /**
   * @return string|null
   */
  public function getName(): ?string
  {
    return $this->name;
  }


}
