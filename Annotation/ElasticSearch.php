<?php
/*
 * This file is part of the Austral EntityTranslate Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\ElasticSearchBundle\Annotation;

use Austral\EntityBundle\Annotation\AustralEntityAnnotation;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target({"CLASS"})
 */
final class ElasticSearch extends AustralEntityAnnotation
{

  /**
   * @var string|null
   */
  public ?string $name = null;

  /**
   * @var array
   */
  public array $fieldAnnotations = array();

  /**
   * @param string|null $name
   * @param array $fieldAnnotations
   */
  public function __construct(?string $name = null, array $fieldAnnotations = array()) {
    $this->name = $name;
    $this->fieldAnnotations = $fieldAnnotations;
  }

}