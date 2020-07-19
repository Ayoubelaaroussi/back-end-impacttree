<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints AS Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\DataSetRepository")
 */
class DataSet
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     * @Assert\IsNull(groups={"new-dataset"})
     */
    private $id;
    
    /**
     * @ORM\Column(type="uuid", unique=true)
     * @Serializer\ReadOnly
     * @Serializer\Type("string")
     * @Serializer\Groups({"new-dataset", "my-dataset"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"new-dataset", "update-dataset"})
     * @Assert\Length(
     *	min = 5,
     *	max = 100,
     *	groups={"new-dataset", "update-dataset"}
     * )
     * @Serializer\Groups({"new-dataset", "update-dataset", "my-dataset"})
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
	 * @Serializer\Groups({"new-dataset", "update-dataset", "my-dataset"})
     */
    private $description;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\NotBlank(groups={"new-dataset", "update-dataset"})
     * @Assert\Type(type="integer", groups={"new-dataset", "update-dataset"})
	 * @Serializer\Groups({"new-dataset", "update-dataset", "my-dataset"})
     */
    private $privacy;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Type("DateTime<'Y-m-d'>")
     * @Serializer\SerializedName("creationDate")
     * @Serializer\Groups({"my-dataset"})
     */
    private $creationDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Searcher", inversedBy="dataSets")
     * @ORM\JoinColumn(nullable=false)
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Part", mappedBy="dataSet")
     * @Serializer\Groups({"my-dataset"})
     */
    private $parts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SurveyToken", mappedBy="dataset", orphanRemoval=true)
     */
    private $surveyTokens;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\FileExcel", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Type("App\Entity\FileExcel")
     * @Serializer\SerializedName("fileExcel")
     * @Serializer\Groups({"new-dataset"})
     * @Assert\Valid(groups={"new-file"})
     */
    private $fileExcel;

    public function __construct()
    {
        $this->parts = new ArrayCollection();
        $this->surveyTokens = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->creationDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getPrivacy(): ?int
    {
        return $this->privacy;
    }

    public function setPrivacy(int $privacy): self
    {
        $this->privacy = $privacy;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function getOwner(): ?Searcher
    {
        return $this->owner;
    }

    public function setOwner(?Searcher $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection|Part[]
     */
    public function getParts(): Collection
    {
        return $this->parts;
    }

    public function addPart(Part $part): self
    {
        if (!$this->parts->contains($part)) {
            $this->parts[] = $part;
            $part->setDataSet($this);
        }

        return $this;
    }

    public function removePart(Part $part): self
    {
        if ($this->parts->contains($part)) {
            $this->parts->removeElement($part);
            // set the owning side to null (unless already changed)
            if ($part->getDataSet() === $this) {
                $part->setDataSet(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SurveyToken[]
     */
    public function getSurveyTokens(): Collection
    {
        return $this->surveyTokens;
    }

    public function addSurveyToken(SurveyToken $surveyToken): self
    {
        if (!$this->surveyTokens->contains($surveyToken)) {
            $this->surveyTokens[] = $surveyToken;
            $surveyToken->setDataset($this);
        }

        return $this;
    }

    public function removeSurveyToken(SurveyToken $surveyToken): self
    {
        if ($this->surveyTokens->contains($surveyToken)) {
            $this->surveyTokens->removeElement($surveyToken);
            // set the owning side to null (unless already changed)
            if ($surveyToken->getDataset() === $this) {
                $surveyToken->setDataset(null);
            }
        }

        return $this;
    }

    public function getFileExcel(): ?FileExcel
    {
        return $this->fileExcel;
    }

    public function setFileExcel(?FileExcel $file): self
    {
        $this->fileExcel = $file;

        return $this;
    }
}