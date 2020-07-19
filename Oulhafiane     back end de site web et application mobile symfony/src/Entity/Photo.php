<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PhotoRepository")
 * @Vich\Uploadable
 */
class Photo
{
	/**
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 * @ORM\Column(type="integer")
	 * @Serializer\ReadOnly
	 * @Assert\IsNull(groups={"Default", "new-photo"})
	 */
	private $id;

	/**
	 * @Vich\UploadableField(mapping="photo_user", fileNameProperty="name", size="size")
	 * @Serializer\Type("string")
	 * @Serializer\Groups({"new-problematic", "update-problematic"})
	 * @Assert\File(
	 *	maxSize = "6M",
	 *	mimeTypes = {
	 *		"image/png",
	 *		"image/jpeg",
	 *		"image/jpg"
	 *	},
	 *	groups={"new-photo"}
	 * )
	 */
	private $file;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $name;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $size;

	/**
	 * @ORM\Column(type="string", length=255)
	 * Assert\Url(groups={"new-photo"})
	 */
	private $link;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $uploadAt;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Problematic", inversedBy="photos")
	 */
	private $problematic;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
	 */

	public function setFile(?File $file = null): void
	{
		$this->file = $file;

		if (null !== $file)
			$this->uploadAt = new \DateTimeImmutable();
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

	public function getSize(): ?int
	{
		return $this->size;
	}

	public function setSize(int $size): self
	{
		$this->size = $size;

		return $this;
	}

	public function getLink(): ?string
	{
		return $this->link;
	}

	public function setLink(string $link): self
	{
		$this->link = $link;

		return $this;
	}

	public function getUploadAt(): ?\DateTimeInterface
	{
		return $this->uploadAt;
	}

	public function setUploadAt(\DateTimeInterface $uploadAt): self
	{
		$this->uploadAt = $uploadAt;

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
}
