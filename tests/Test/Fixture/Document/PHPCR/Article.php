<?php

namespace Test\Fixture\Document\PHPCR;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRAnnotations;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * @PHPCRAnnotations\Document
 */
#[PHPCR\Document]
final class Article
{
    /**
     * @PHPCRAnnotations\Id
     */
    #[PHPCR\Id]
    private $id;

    /**
     * @PHPCRAnnotations\ParentDocument
     */
    #[PHPCR\ParentDocument]
    private $parent;

    /**
     * @PHPCRAnnotations\Field(type="string")
     */
    #[PHPCR\Field(type: "string")]
    private $title;

    public function getId()
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
