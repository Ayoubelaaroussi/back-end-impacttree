<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SearcherRepository")
 */
class Searcher extends User
{
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Problematic", mappedBy="owner", orphanRemoval=true)
     */
    private $problematics;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Vote", mappedBy="voter", orphanRemoval=true)
     */
    private $votes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="owner", orphanRemoval=true)
     */
    private $comments;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="follow")
     */
    private $followers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DataSet", mappedBy="owner")
     */
    private $dataSets;

    public function __construct()
    {
        $this->problematics = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->domains = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->dataSets = new ArrayCollection();
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
            $problematic->setOwner($this);
        }

        return $this;
    }

    public function removeProblematic(Problematic $problematic): self
    {
        if ($this->problematics->contains($problematic)) {
            $this->problematics->removeElement($problematic);
            // set the owning side to null (unless already changed)
            if ($problematic->getOwner() === $this) {
                $problematic->setOwner(null);
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
            $vote->setVoter($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): self
    {
        if ($this->votes->contains($vote)) {
            $this->votes->removeElement($vote);
            // set the owning side to null (unless already changed)
            if ($vote->getVoter() === $this) {
                $vote->setVoter(null);
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
            $comment->setOwner($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getOwner() === $this) {
                $comment->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(User $follower): self
    {
        if (!$this->followers->contains($follower)) {
            $this->followers[] = $follower;
            $follower->addFollow($this);
        }

        return $this;
    }

    public function removeFollower(User $follower): self
    {
        if ($this->followers->contains($follower)) {
            $this->followers->removeElement($follower);
            $follower->removeFollow($this);
        }

        return $this;
    }

    /**
     * @return Collection|DataSet[]
     */
    public function getDataSets(): Collection
    {
        return $this->dataSets;
    }

    public function addDataSet(DataSet $dataSet): self
    {
        if (!$this->dataSets->contains($dataSet)) {
            $this->dataSets[] = $dataSet;
            $dataSet->setOwner($this);
        }

        return $this;
    }

    public function removeDataSet(DataSet $dataSet): self
    {
        if ($this->dataSets->contains($dataSet)) {
            $this->dataSets->removeElement($dataSet);
            // set the owning side to null (unless already changed)
            if ($dataSet->getOwner() === $this) {
                $dataSet->setOwner(null);
            }
        }

        return $this;
    }
}
