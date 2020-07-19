<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AdminRepository")
 */
class Admin extends User
{
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SearcherApplications", mappedBy="acceptedBy")
     */
    private $searcherApplications;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\News", mappedBy="creator")
     */
    private $news;

    public function __construct()
    {
        $this->searcherApplications = new ArrayCollection();
        $this->news = new ArrayCollection();
    }

    /**
     * @return Collection|SearcherApplications[]
     */
    public function getSearcherApplications(): Collection
    {
        return $this->searcherApplications;
    }

    public function addSearcherApplication(SearcherApplications $searcherApplication): self
    {
        if (!$this->searcherApplications->contains($searcherApplication)) {
            $this->searcherApplications[] = $searcherApplication;
            $searcherApplication->setAcceptedBy($this);
        }

        return $this;
    }

    public function removeSearcherApplication(SearcherApplications $searcherApplication): self
    {
        if ($this->searcherApplications->contains($searcherApplication)) {
            $this->searcherApplications->removeElement($searcherApplication);
            // set the owning side to null (unless already changed)
            if ($searcherApplication->getAcceptedBy() === $this) {
                $searcherApplication->setAcceptedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|News[]
     */
    public function getNews(): Collection
    {
        return $this->news;
    }

    public function addNews(News $news): self
    {
        if (!$this->news->contains($news)) {
            $this->news[] = $news;
            $news->setCreator($this);
        }

        return $this;
    }

    public function removeNews(News $news): self
    {
        if ($this->news->contains($news)) {
            $this->news->removeElement($news);
            // set the owning side to null (unless already changed)
            if ($news->getCreator() === $this) {
                $news->setCreator(null);
            }
        }

        return $this;
    }
}
