<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ManagerBundle\Model\ElasticSearch;

use Austral\ElasticSearchBundle\Annotation\ElasticSearchField;
use Austral\EntityBundle\Entity\EntityInterface;
use Exception;

/**
 * Austral ElasticSearch DataHydrate Model.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class DataHydrate
{

  /**
   * @var string
   */
  protected string $objectClassname;

  /**
   * @var string
   */
  protected string $objectId;

  /**
   * @var string
   */
  protected string $language;

  /**
   * @var string
   */
  protected string $title;

  /**
   * @var string
   */
  protected string $refH1;

  /**
   * @var string|null
   */
  protected ?string $refTitle = null;

  /**
   * @var string|null
   */
  protected ?string $refDescription = null;

  /**
   * @var string|null
   */
  protected ?string $refUrl = null;

  /**
   * @var string|null
   */
  protected ?string $status = null;

  /**
   * @var string|null
   */
  protected ?string $content = null;

  /**
   * @var array
   */
  protected array $extraContent = array();

  /**
   * @param EntityInterface $object
   *
   * @return DataHydrate
   * @throws Exception
   */
  public static function create(EntityInterface $object): DataHydrate
  {
    return new self($object);
  }

  /**
   * DataHydrate constructor.
   * @throws Exception
   */
  public function __construct(EntityInterface $object)
  {
    $this->objectId = $object->getId();
    $this->objectClassname = $object->getClassname();
  }

  /**
   * @return array
   */
  public function toArray(): array
  {
    return array(
      ElasticSearchField::NAME_TITLE            => $this->title,
      ElasticSearchField::NAME_REF_TITLE        => $this->refTitle,
      ElasticSearchField::NAME_REF_DESCRIPTION  => $this->refDescription,
      ElasticSearchField::NAME_REF_URL          => $this->refUrl,
      ElasticSearchField::NAME_LANGUAGE         => $this->language,
      ElasticSearchField::NAME_STATUS           => $this->status,
      ElasticSearchField::NAME_CONTENT          => $this->content,
      ElasticSearchField::NAME_EXTRA_CONTENT    => $this->extraContent,
    );
  }

  /**
   * @return string
   */
  public function getObjectClassname(): string
  {
    return $this->objectClassname;
  }

  /**
   * @param string $objectClassname
   *
   * @return $this
   */
  public function setObjectClassname(string $objectClassname): DataHydrate
  {
    $this->objectClassname = $objectClassname;
    return $this;
  }

  /**
   * @return string
   */
  public function getObjectId(): string
  {
    return $this->objectId;
  }

  /**
   * @param string $objectId
   *
   * @return $this
   */
  public function setObjectId(string $objectId): DataHydrate
  {
    $this->objectId = $objectId;
    return $this;
  }

  /**
   * @return string
   */
  public function getLanguage(): string
  {
    return $this->language;
  }

  /**
   * @param string $language
   *
   * @return $this
   */
  public function setLanguage(string $language): DataHydrate
  {
    $this->language = $language;
    return $this;
  }

  /**
   * @return string
   */
  public function getTitle(): string
  {
    return $this->title;
  }

  /**
   * @param string $title
   *
   * @return $this
   */
  public function setTitle(string $title): DataHydrate
  {
    $this->title = $title;
    return $this;
  }

  /**
   * @return string
   */
  public function getRefH1(): string
  {
    return $this->refH1;
  }

  /**
   * @param string $refH1
   *
   * @return $this
   */
  public function setRefH1(string $refH1): DataHydrate
  {
    $this->refH1 = $refH1;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getRefTitle(): ?string
  {
    return $this->refTitle;
  }

  /**
   * @param string|null $refTitle
   *
   * @return $this
   */
  public function setRefTitle(?string $refTitle): DataHydrate
  {
    $this->refTitle = $refTitle;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getRefDescription(): ?string
  {
    return $this->refDescription;
  }

  /**
   * @param string|null $refDescription
   *
   * @return $this
   */
  public function setRefDescription(?string $refDescription): DataHydrate
  {
    $this->refDescription = $refDescription;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getRefUrl(): ?string
  {
    return $this->refUrl;
  }

  /**
   * @param string|null $refUrl
   *
   * @return $this
   */
  public function setRefUrl(?string $refUrl): DataHydrate
  {
    $this->refUrl = $refUrl;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getStatus(): ?string
  {
    return $this->status;
  }

  /**
   * @param string|null $status
   *
   * @return $this
   */
  public function setStatus(?string $status): DataHydrate
  {
    $this->status = $status;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getContent(): ?string
  {
    return $this->content;
  }

  /**
   * @param string|null $content
   *
   * @return $this
   */
  public function setContent(?string $content): DataHydrate
  {
    $this->content = $content;
    return $this;
  }

  /**
   * @return array
   */
  public function getExtraContent(): array
  {
    return $this->extraContent;
  }

  /**
   * @param string $key
   * @param string $value
   *
   * @return $this
   */
  public function addExtraContent(string $key, string $value): DataHydrate
  {
    $this->extraContent[$key] = $value;
    return $this;
  }

  /**
   * @param array $extraContent
   *
   * @return $this
   */
  public function setExtraContent(array $extraContent): DataHydrate
  {
    $this->extraContent = $extraContent;
    return $this;
  }

}