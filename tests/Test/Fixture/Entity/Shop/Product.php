<?php

namespace Test\Fixture\Entity\Shop;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=64)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(length=255, nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="float", nullable=false)
     */
    private ?float $price = null;

    /**
     * @ORM\ManyToMany(targetEntity="Tag")
     */
    private Collection $tags;

    /**
     * @ORM\Column(type="integer")
     */
    private int $numTags = 0;

    public function __construct()
    {
        $this->tags = new ArrayCollection;
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

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function addTag(Tag $tag): void
    {
        $this->numTags++;
        $this->tags[] = $tag;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }
}
