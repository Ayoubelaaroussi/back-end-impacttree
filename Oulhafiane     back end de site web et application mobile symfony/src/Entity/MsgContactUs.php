<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints AS Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\MsgContactUsRepository")
 */
class MsgContactUs
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     * @Serializer\Groups({"list-msgs"})
     * @Assert\IsNull(groups={"new-msg"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"new-msg"})
	 * @Assert\Length(
	 *	min = 5,
	 *	max = 100,
	 *	groups={"new-msg"}
	 * )
     * @Serializer\SerializedName("fullName")
	 * @Serializer\Groups({"new-msg", "list-msgs"})
     */
    private $fullName;

    /**
     * @ORM\Column(type="string", length=180)
     * @Assert\NotBlank(groups={"new-msg"})
	 * @Assert\Length(
	 *	min = 5,
	 *	max = 180,
	 *	groups={"new-msg"}
	 * )
     * @Assert\Email(groups={"new-msg"})
	 * @Serializer\Groups({"new-msg", "list-msgs"})
     */
    private $email;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(groups={"new-msg"})
     * @Serializer\Groups({"new-msg", "list-msgs"})
     */
    private $message;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"list-msgs"})
     */
    private $date;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"list-msgs"})
     */
    private $seen;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->date = new \DateTime();
        $this->seen = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

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

    public function getSeen(): ?bool
    {
        return $this->seen;
    }

    public function setSeen(bool $seen): self
    {
        $this->seen = $seen;

        return $this;
    }
}
