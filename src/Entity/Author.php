<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableEntity;
use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AuthorRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Author
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $chatUsername;

    /**
     * @ORM\ManyToMany(targetEntity=Review::class, mappedBy="reviewers")
     */
    private $reviews;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="author")
     */
    private $comments;

    /**
     * @ORM\OneToOne(targetEntity=AuthorBlacklist::class, mappedBy="author", cascade={"persist"})
     */
    private $authorBlacklist;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getChatUsername(): ?string
    {
        return $this->chatUsername;
    }

    public function setChatUsername(?string $chatUsername): self
    {
        $this->chatUsername = $chatUsername;

        return $this;
    }

    /**
     * @return Collection|Review[]
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews[] = $review;
            $review->addReviewer($this);
        }

        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->reviews->contains($review)) {
            $this->reviews->removeElement($review);
            $review->removeReviewer($this);
        }

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getAuthorBlacklist(): ?AuthorBlacklist
    {
        return $this->authorBlacklist;
    }

    public function setAuthorBlacklist(AuthorBlacklist $authorBlacklist): self
    {
        $this->authorBlacklist = $authorBlacklist;

        // set the owning side of the relation if necessary
        if ($authorBlacklist->getAuthor() !== $this) {
            $authorBlacklist->setAuthor($this);
        }

        return $this;
    }
}
