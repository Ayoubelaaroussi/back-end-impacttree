<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\CommentRepository")
 */
class Comment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     * @Serializer\Groups({"list-comments"})
     * @Assert\IsNull(groups={"new-comment"})
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(groups={"new-comment", "update-comment"})
	 * @Serializer\Groups({"list-comments"})
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Searcher", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"list-comments"})
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Problematic", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $problematic;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Vote", mappedBy="comment", orphanRemoval=true)
     */
    private $votes;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Type("DateTime<'Y-m-d h:m:s'>")
     * @Serializer\SerializedName("creationDate")
     * @Serializer\Groups({"list-comments"})
     */
    private $creationDate;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->creationDate = new \DateTime();
    }

    public function __construct()
    {
        $this->votes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

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

    public function getProblematic(): ?Problematic
    {
        return $this->problematic;
    }

    public function setProblematic(?Problematic $problematic): self
    {
        $this->problematic = $problematic;

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
            $vote->setComment($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): self
    {
        if ($this->votes->contains($vote)) {
            $this->votes->removeElement($vote);
            // set the owning side to null (unless already changed)
            if ($vote->getComment() === $this) {
                $vote->setComment(null);
            }
        }

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }
}
