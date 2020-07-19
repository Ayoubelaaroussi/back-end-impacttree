<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints AS Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\NewsRepository")
 */
class News
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     * @Serializer\Groups({"list-news"})
     * @Assert\IsNull(groups={"new-news"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(groups={"new-news", "update-news"})
	 * @Assert\Length(
	 *	min = 5,
	 *	max = 100,
	 *	groups={"new-news", "update-news"}
	 * )
	 * @Serializer\Groups({"new-news", "update-news", "list-news"})
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(groups={"new-news", "update-news"})
	 * @Serializer\Groups({"new-news", "update-news", "list-news"})
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank(groups={"new-news", "update-news"})
     * @Assert\DateTime(groups={"new-news", "update-news"})
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
	 * @Serializer\Groups({"new-news", "update-news", "list-news"})
     */
    private $date;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Admin", inversedBy="news")
     * @ORM\JoinColumn(nullable=false)
     */
    private $creator;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(groups={"new-news", "update-news"})
     * @Serializer\SerializedName("isEvent")
	 * @Serializer\Groups({"new-news", "update-news", "list-news"})
     */
    private $isEvent;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    public function getCreator(): ?Admin
    {
        return $this->creator;
    }

    public function setCreator(?Admin $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getIsEvent(): ?bool
    {
        return $this->isEvent;
    }

    public function setIsEvent(bool $isEvent): self
    {
        $this->isEvent = $isEvent;

        return $this;
    }
}
