<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints AS Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\ProblematicRepository")
 */
class Problematic
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     * @Serializer\Groups({"list-problematics"})
     * @Assert\IsNull(groups={"new-problematic"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(groups={"new-problematic", "update-problematic"})
	 * @Assert\Length(
	 *	min = 3,
	 *	max = 100,
	 *	groups={"new-problematic", "update-problematic"}
	 * )
	 * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(groups={"new-problematic", "update-problematic"})
	 * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $solution;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $advantage;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\SerializedName("possibleApplication")
     * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $possibleApplication;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $link;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Searcher", inversedBy="problematics")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"list-problematics"})
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SubCategory", inversedBy="problematics")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank(groups={"new-problematic"})
     * @Serializer\Type("App\Entity\SubCategory")
	 * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Photo", mappedBy="problematic", cascade={"persist"})
     * @Serializer\Type("ArrayCollection<App\Entity\Photo>")
	 * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
	 * @Assert\Valid(groups={"new-photo"})
     */
    private $photos;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="problematic", orphanRemoval=true)
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Vote", mappedBy="problematic", orphanRemoval=true)
     */
    private $votes;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Type("DateTime<'Y-m-d'>")
     * @Serializer\SerializedName("creationDate")
     * @Serializer\Groups({"list-problematics"})
     */
    private $creationDate;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @Serializer\Type("array")
	 * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $keywords = [];

    /**
     * @ORM\Column(type="array", nullable=true)
     * @Serializer\Type("array")
	 * @Serializer\Groups({"new-problematic", "update-problematic", "list-problematics"})
     */
    private $researchers = [];

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->creationDate = new \DateTime();
    }

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->votes = new ArrayCollection();
        //$this->photos = new ArrayCollection();
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

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function setSolution(?string $solution): self
    {
        $this->solution = $solution;

        return $this;
    }

    public function getAdvantage(): ?string
    {
        return $this->advantage;
    }

    public function setAdvantage(?string $advantage): self
    {
        $this->advantage = $advantage;

        return $this;
    }

    public function getPossibleApplication(): ?string
    {
        return $this->possibleApplication;
    }

    public function setPossibleApplication(?string $possibleApplication): self
    {
        $this->possibleApplication = $possibleApplication;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;

        return $this;
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

    public function getCategory(): ?SubCategory
    {
        return $this->category;
    }

    public function setCategory(?SubCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection|Photo[]
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos[] = $photo;
            $photo->setProblematic($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): self
    {
        if ($this->photos->contains($photo)) {
            $this->photos->removeElement($photo);
            // set the owning side to null (unless already changed)
            if ($photo->getProblematic() === $this) {
                $photo->setProblematic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setProblematic($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getProblematic() === $this) {
                $comment->setProblematic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Vote[]
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(Vote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes[] = $vote;
            $vote->setProblematic($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): self
    {
        if ($this->votes->contains($vote)) {
            $this->votes->removeElement($vote);
            // set the owning side to null (unless already changed)
            if ($vote->getProblematic() === $this) {
                $vote->setProblematic(null);
            }
        }

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getResearchers(): ?array
    {
        return $this->researchers;
    }

    public function setResearchers(?array $researchers): self
    {
        $this->researchers = $researchers;

        return $this;
    }
}
