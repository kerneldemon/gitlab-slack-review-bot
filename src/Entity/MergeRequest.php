<?php

namespace App\Entity;

use App\Constant\MergeRequest\MergeStatus;
use App\Entity\Traits\TimestampableEntity;
use App\Repository\MergeRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MergeRequestRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class MergeRequest
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $iid;

    /**
     * @ORM\Column(type="string")
     */
    private $mergeStatus;

    /**
     * @ORM\Column(type="string")
     */
    private $state;

    /**
     * @ORM\Column(type="string")
     */
    private $url;

    /**
     * @ORM\ManyToOne(targetEntity=Author::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $project;

    /**
     * @ORM\OneToOne(targetEntity=Review::class, mappedBy="mergeRequest", cascade={"persist", "remove"})
     */
    private $review;

    public function __construct()
    {
        $this->mergeStatus = MergeStatus::UNKNOWN;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getIid()
    {
        return $this->iid;
    }

    public function setIid($iid): self
    {
        $this->iid = $iid;

        return $this;
    }

    public function getMergeStatus(): ?string
    {
        return $this->mergeStatus;
    }

    public function setMergeStatus(string $status): self
    {
        $this->mergeStatus = $status;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(Review $review): self
    {
        $this->review = $review;

        // set the owning side of the relation if necessary
        if ($review->getMergeRequest() !== $this) {
            $review->setMergeRequest($this);
        }

        return $this;
    }
}
