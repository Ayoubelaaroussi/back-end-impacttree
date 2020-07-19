<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\SearcherApplicationsRepository")
 */
class SearcherApplications
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     * @Serializer\Groups({"list-applications"})
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="searcherApplication", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"list-applications"})
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Admin", inversedBy="searcherApplications")
     */
    private $acceptedBy;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Serializer\Groups({"list-applications"})
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"list-applications"})
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAcceptedBy(): ?Admin
    {
        return $this->acceptedBy;
    }

    public function setAcceptedBy(?Admin $acceptedBy): self
    {
        $this->acceptedBy = $acceptedBy;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }
}
