<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FileExcelRepository")
 * @Vich\Uploadable
 */
class FileExcel
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
	 * @Vich\UploadableField(mapping="file_excel", fileNameProperty="name", size="size")
	 * @Serializer\Type("string")
	 * @Serializer\Groups({"new-dataset"})
	 * @Assert\File(
	 *	maxSize = "50M",
	 *	mimeTypes = {
	 *		"text/csv",
	 *		"application/vnd.ms-excel",
	 *		"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
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
	 * Assert\Url(groups={"my-dataset"})
	 */
	private $link;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $uploadAt;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Dataset", inversedBy="fileExcel")
	 */
	private $dataset;

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

	public function getDataset(): ?Dataset
	{
		return $this->dataset;
	}

	public function setDataset(?Dataset $dataset): self
	{
		$this->dataset = $dataset;

		return $this;
	}
}
