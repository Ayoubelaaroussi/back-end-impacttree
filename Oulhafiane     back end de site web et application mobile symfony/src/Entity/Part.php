<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints AS Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PartRepository")
 */
class Part
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     * @Serializer\Groups({"my-dataset"})
     * @Assert\IsNull(groups={"new-part"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(groups={"new-part", "update-part"})
     * @Assert\Length(
     *	min = 5,
     *	max = 100,
     *	groups={"new-part", "update-part"}
     * )
     * @Serializer\Groups({"new-part", "update-part", "my-dataset"})
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"new-part", "update-part", "my-dataset"})
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\DataSet", inversedBy="parts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $dataSet;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Variable", mappedBy="part")
     * @Serializer\Groups({"new-part", "add-variables", "my-dataset"})
     */
    private $variables;

    public function __construct()
    {
        $this->variables = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDataSet(): ?DataSet
    {
        return $this->dataSet;
    }

    public function setDataSet(?DataSet $dataSet): self
    {
        $this->dataSet = $dataSet;

        return $this;
    }

    /**
     * @return Collection|Variable[]
     */
    public function getVariables(): ?Collection
    {
        return $this->variables;
    }

    public function addVariable(Variable $variable): self
    {
        if (!$this->variables->contains($variable)) {
            $this->variables[] = $variable;
            $variable->setPart($this);
        }

        return $this;
    }

    public function removeVariable(Variable $variable): self
    {
        if ($this->variables->contains($variable)) {
            $this->variables->removeElement($variable);
            // set the owning side to null (unless already changed)
            if ($variable->getPart() === $this) {
                $variable->setPart(null);
            }
        }

        return $this;
    }
}
