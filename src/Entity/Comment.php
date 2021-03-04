<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableEntity;
use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommentRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Comment
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $note;

    /**
     * @ORM\ManyToOne(targetEntity=MergeRequest::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $mergeRequest;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $project;

    /**
     * @ORM\ManyToOne(targetEntity=Author::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\ManyToOne(targetEntity=Review::class, inversedBy="comments")
     */
    private $review;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;

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

    public function setReview(?Review $review): self
    {
        $this->review = $review;

        return $this;
    }
}
