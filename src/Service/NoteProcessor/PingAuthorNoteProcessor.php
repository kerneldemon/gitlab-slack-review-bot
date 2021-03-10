<?php

declare(strict_types=1);

namespace App\Service\NoteProcessor;

use App\Constant\Note\Approval;
use App\Constant\Review\Status;
use App\Entity\Comment;
use App\Entity\Review;
use App\Service\ReviewService;

class PingAuthorNoteProcessor implements NoteProcessorInterface
{
    private const REVIEW_TAG = '@review/author';

    private ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function supports(Comment $comment): bool
    {
        return stripos($comment->getNote(), self::REVIEW_TAG) !== false;
    }

    public function process(Comment $comment): void
    {
        $review = $this->reviewService->createIfNotExists($comment);
        $this->reviewService->notifyAboutComments($review);
    }
}
