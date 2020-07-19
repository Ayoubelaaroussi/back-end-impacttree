<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\SurveyTokenRepository")
 */
class SurveyToken implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\ReadOnly
     */
    private $id;

    /**
     * @ORM\Column(type="uuid", unique=true)
     * @Serializer\ReadOnly
     * @Serializer\Type("string")
     * @Serializer\Groups({"tokens"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="smallint")
     * @Serializer\Groups({"tokens"})
     */
    private $privacy;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @Serializer\Type("array")
     * @Serializer\Groups({"tokens"})
     */
    private $emails = [];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\DataSet", inversedBy="surveyTokens")
     * @ORM\JoinColumn(nullable=false)
     */
    private $dataset;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"tokens"})
     */
    private $creationDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Groups({"tokens"})
     */
    private $expirationDate;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->creationDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
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

    public function getEmails(): ?array
    {
        return $this->emails;
    }

    public function setEmails(?array $emails): self
    {
        $this->emails = $emails;

        return $this;
    }

    public function getDataset(): ?DataSet
    {
        return $this->dataset;
    }

    public function setDataset(?DataSet $dataset): self
    {
        $this->dataset = $dataset;

        return $this;
    }

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?\DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return string[] The user roles
     */
    public function getRoles()
    {
        $roles[] = 'ROLE_IMPLEMENT';

        return array_unique($roles);
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string|null The encoded password if any
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {

    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return (string) $this->uuid;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {

    }
}
