<?php

namespace Test\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
final class Article
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private ?string $title = null;

    /**
     * @ODM\Field(type="bool", name="status")
     */
    private bool $status = false;

    /**
     * @ODM\Field(type="date", name="created_at")
     */
    private ?\DateTime $createdAt = null;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }
}

