<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 */
class SubCategory
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"new-problematic", "list-problematics", "list-categories"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Serializer\Groups({"list-problematics", "list-categories"})
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Problematic", mappedBy="category", orphanRemoval=true)
     */
    private $problematics;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="subCategories")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\SerializedName("parent_category")
     * @Serializer\Groups({"list-problematics"})
     */
    private $category;

    public function __construct()
    {
        $this->problematics = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection|Problematic[]
     */
    public function getProblematics(): Collection
    {
        return $this->problematics;
    }

    public function addProblematic(Problematic $problematic): self
    {
        if (!$this->problematics->contains($problematic)) {
            $this->problematics[] = $problematic;
            $problematic->setCategory($this);
        }

        return $this;
    }

    public function removeProblematic(Problematic $problematic): self
    {
        if ($this->problematics->contains($problematic)) {
            $this->problematics->removeElement($problematic);
            // set the owning side to null (unless already changed)
            if ($problematic->getCategory() === $this) {
                $problematic->setCategory(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }
}
