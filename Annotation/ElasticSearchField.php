<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
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
 * @Target({"PROPERTY", "ANNOTATION", "METHOD"})
 */
final class ElasticSearchField extends AustralEntityAnnotation
{

  const INDEX_TYPE = "index_type";
  const OBJECT_NAME = "object_type";

  const NAME_TITLE = "title";
  const NAME_REF_H1 = "ref_h1";
  const NAME_REF_TITLE = "ref_title";
  const NAME_REF_DESCRIPTION = "ref_description";
  const NAME_REF_URL = "ref_url";
  const NAME_STATUS = "status";
  const NAME_CONTENT = "content";
  const NAME_LANGUAGE = "language";
  const NAME_UPDATE = "update";
  const NAME_EXTRA_CONTENT = "extra_content";

  /**
   * @var string|null
   */
  public ?string $name = "";

  /**
   * @var string|null
   */
  public ?string $value = "";

  /**
   * @param string|null $name
   * @param string|null $keyname
   * @param string|null $value
   */
  public function __construct(?string $name = null, ?string $keyname = null, ?string $value = null) {
    $this->name = $name;
    if($keyname) {
      $this->keyname = $keyname;
    }
    $this->value = $value;
  }

}