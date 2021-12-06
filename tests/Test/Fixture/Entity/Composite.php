<?php

namespace Test\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Composite
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private ?string $title = null;

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private ?string $uid = null;

    /**
     * Sets uid.
     */
    public function setUid(?string $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * Returns uid.
     */
    public function getUid(): ?string
    {
        return $this->uid;
    }

    /**
     * Sets Id.
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns Id.
     */
    public function getId(): ?int
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
