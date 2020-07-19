<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\VoteRepository")
 */
class Vote
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     * @Serializer\Groups({"list-votes"})
     * @Assert\IsNull(groups={"new-vote"})
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"new-vote", "old-vote", "list-votes"})
     */
    private $good;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Searcher", inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $voter;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Problematic", inversedBy="votes")
     */
    private $problematic;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Comment", inversedBy="votes")
     */
    private $comment;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Type("DateTime<'Y-m-d h:m:s'>")
     * @Serializer\SerializedName("creationDate")
     * @Serializer\Groups({"list-votes"})
     */
    private $creationDate;

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

    public function getGood(): ?bool
    {
        return $this->good;
    }

    public function setGood(bool $good): self
    {
        $this->good = $good;

        return $this;
    }

    public function getVoter(): ?Searcher
    {
        return $this->voter;
    }

    public function setVoter(?Searcher $voter): self
    {
        $this->voter = $voter;

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

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }
}
