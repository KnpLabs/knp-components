<?php

namespace Test\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
final class Image
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field
     */
    private ?string $title = null;

    /**
     * @ODM\File
     * @var int|string
     */
    private $file;

    /**
     * Set file.
     *
     * @param int|string $file
     */
    public function setFile($file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file.
     *
     * @return int|string
     */
    public function getFile()
    {
        return $this->file;
    }

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
}
