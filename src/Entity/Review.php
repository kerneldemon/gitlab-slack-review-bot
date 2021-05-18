<?php

namespace App\Entity;

use App\Constant\Review\Status;
use App\Entity\Traits\TimestampableEntity;
use App\Repository\ReviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReviewRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Review
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=MergeRequest::class, inversedBy="review")
     * @ORM\JoinColumn(nullable=false)
     */
    private $mergeRequest;

    /**
     * @ORM\ManyToMany(targetEntity=Author::class, inversedBy="reviews")
     */
    private $reviewers;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $scope;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="review")
     */
    private $comments;

    /**
     * @ORM\Column(type="integer")
     */
    private $approvalCount;

    public function __construct()
    {
        $this->reviewers = new ArrayCollection();
        $this->status = Status::NEW;
        $this->comments = new ArrayCollection();
        $this->approvalCount = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMergeRequest(): ?MergeRequest
    {
        return $this->mergeRequest;
    }

    public function setMergeRequest(MergeRequest $mergeRequest): self
    {
        $this->mergeRequest = $mergeRequest;

        return $this;
    }

    /**
     * @return Collection|Author[]
     */
    public function getReviewers(): Collection
    {
        return $this->reviewers;
    }

    public function addReviewer(Author $reviewer): self
    {
        if (!$this->reviewers->contains($reviewer)) {
            $this->reviewers[] = $reviewer;
        }

        return $this;
    }

    public function removeReviewer(Author $reviewer): self
    {
        if ($this->reviewers->contains($reviewer)) {
            $this->reviewers->removeElement($reviewer);
        }

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setReview($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getReview() === $this) {
                $comment->setReview(null);
            }
        }

        return $this;
    }

    public function getApprovalCount(): ?int
    {
        return $this->approvalCount;
    }

    public function setApprovalCount(int $approval_count): self
    {
        $this->approvalCount = $approval_count;

        return $this;
    }

    public function increaseApprovalCount(): self
    {
        $this->approvalCount++;

        return $this;
    }
}
