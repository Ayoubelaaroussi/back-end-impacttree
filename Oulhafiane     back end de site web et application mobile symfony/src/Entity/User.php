<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"searcher" = "Searcher", "customer" = "Customer", "admin" = "Admin"})
 * @Serializer\Discriminator(field = "type", disabled = false, map = {"searcher" = "App\Entity\Searcher", "customer": "App\Entity\Customer", "admin" = "App\Entity\Admin"})
 */
abstract class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Assert\IsNull(groups={"new-user"})
     * @Serializer\ReadOnly
     */
    private $id;

    /**
     * @ORM\Column(type="uuid", unique=true)
     * @Serializer\Type("string")
     * @Serializer\Groups({"infos", "list-comments", "list-problematics", "list-applications", "all-profiles"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank(groups={"new-user"})
     * @Assert\Email(groups={"new-user"})
     * @Serializer\Groups({"new-user", "infos"})
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Serializer\ReadOnly
     * @Assert\IsNull(groups={"new-user"})
     */
    private $password;

    /**
     * @Assert\NotBlank(groups={"new-user"})
     * @Assert\Length(min=6, max=4096, groups={"new-user"})
     * @Assert\NotCompromisedPassword(groups={"new-user"})
     * @Serializer\SerializedName("password")
     * @Serializer\Type("string")
     * @Serializer\Groups({"new-user"})
     */
    protected $plainPassword;


    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(groups={"new-user"})
     * @Serializer\SerializedName("firstName")
     * @Serializer\Groups({"new-user", "infos", "update-user", "list-comments", "list-problematics", "public", "all-profiles"})
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(groups={"new-user"})
     * @Serializer\SerializedName("lastName")
     * @Serializer\Groups({"new-user", "infos", "update-user", "list-comments", "list-problematics", "public", "all-profiles"})
     */
    private $lastName;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Photo", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"update-user", "infos", "list-comments", "list-problematics", "public"})
     */
    private $Photo;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\NotBlank(groups={"new-user"})
     * @Serializer\Groups({"new-user", "update-user", "infos", "public"})
     */
    private $organization;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\NotBlank(groups={"new-user"})
     * @Serializer\SerializedName("jobTitle")
     * @Serializer\Groups({"new-user", "update-user", "infos", "public"})
     */
    private $jobTitle;
    
    /**
    * @ORM\Column(type="string", length=255, nullable=true)
    * @Serializer\SerializedName("organizationAddress")
    * @Serializer\Groups({"update-user", "infos"})
    */
    private $organizationAddress;

    /**
        * @ORM\Column(type="string", length=50, nullable=true)
        * @Serializer\SerializedName("organizationCity")
        * @Serializer\Groups({"update-user", "infos", "public"})
        */
    private $organizationCity;

    /**
        * @ORM\Column(type="string", length=50, nullable=true)
        * @Serializer\SerializedName("organizationCountry")
        * @Serializer\Groups({"update-user", "infos", "public"})
        */
    private $organizationCountry;

    /**
        * @ORM\Column(type="text", nullable=true)
        * @Serializer\Groups({"update-user", "infos", "public"})
        */
    private $bio;

    /**
        * @ORM\Column(type="string", length=20, nullable=true)
        * @Serializer\Groups({"new-user", "infos", "update-user"})
        */
    private $phone;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $subscriptionDate;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\SearcherApplications", mappedBy="user", cascade={"persist", "remove"})
     */
    private $searcherApplication;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Category", inversedBy="users")
     * @Serializer\Type("ArrayCollection<App\Entity\Category>")
	 * @Serializer\Groups({"update-user", "infos", "public"})
	 * @Assert\Valid(groups={"update-user"})
     */
    private $domains;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Searcher", inversedBy="followers")
     */
    private $follow;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Notification", mappedBy="owner", orphanRemoval=true)
     * @Serializer\Groups({"notifications"})
     */
    private $notifications;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $recoveryToken;

    public function __construct()
    {
        $this->follow = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->uuid = Uuid::uuid4()->toString();
        if ($this instanceof Searcher)
            $this->roles[] = 'ROLE_SEARCHER';
        else if ($this instanceof Reseller)
            $this->roles[] = 'ROLE_CUSTOMER';
        else if ($this instanceof Admin)
            $this->roles[] = 'ROLE_ADMIN';
        $this->subscriptionDate = new \DateTime();
        $this->setIsActive(True);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPlainPassword(): string
    {
        return (string) $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoto(): ?Photo
    {
        return $this->Photo;
    }

    public function setPhoto(?Photo $Photo): self
    {
        $this->Photo = $Photo;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }
    
    public function getOrganizationAddress(): ?string
    {
        return $this->organizationAddress;
    }

    public function setOrganizationAddress(?string $address): self
    {
        $this->organizationAddress = $address;

        return $this;
    }

    public function getOrganizationCity(): ?string
    {
        return $this->organizationCity;
    }

    public function setOrganizationCity(?string $city): self
    {
        $this->organizationCity = $city;

        return $this;
    }

    public function getOrganizationCountry(): ?string
    {
        return $this->organizationCountry;
    }

    public function setOrganizationCountry(?string $country): self
    {
        $this->organizationCountry = $country;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSubscriptionDate(): ?\DateTimeInterface
    {
        return $this->subscriptionDate;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getSearcherApplication(): ?SearcherApplications
    {
        return $this->searcherApplication;
    }

    public function setSearcherApplication(SearcherApplications $searcherApplication): self
    {
        $this->searcherApplication = $searcherApplication;

        // set the owning side of the relation if necessary
        if ($this !== $searcherApplication->getUser()) {
            $searcherApplication->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getDomains(): ?Collection
    {
        return $this->domains;
    }

    public function addDomain(Category $domain): self
    {
        if (!$this->domains->contains($domain)) {
            $this->domains[] = $domain;
        }

        return $this;
    }

    public function removeDomain(Category $domain): self
    {
        if ($this->domains->contains($domain)) {
            $this->domains->removeElement($domain);
        }

        return $this;
    }

    /**
     * @return Collection|Searcher[]
     */
    public function getFollow(): Collection
    {
        return $this->follow;
    }

    public function addFollow(Searcher $follow): self
    {
        if (!$this->follow->contains($follow)) {
            $this->follow[] = $follow;
        }

        return $this;
    }

    public function removeFollow(Searcher $follow): self
    {
        if ($this->follow->contains($follow)) {
            $this->follow->removeElement($follow);
        }

        return $this;
    }

    /**
     * @return Collection|Notification[]
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setOwner($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->contains($notification)) {
            $this->notifications->removeElement($notification);
            // set the owning side to null (unless already changed)
            if ($notification->getOwner() === $this) {
                $notification->setOwner(null);
            }
        }

        return $this;
    }

    public function getRecoveryToken(): ?string
    {
        return $this->recoveryToken;
    }

    public function setRecoveryToken(?string $recoveryToken): self
    {
        $this->recoveryToken = $recoveryToken;

        return $this;
    }
}
