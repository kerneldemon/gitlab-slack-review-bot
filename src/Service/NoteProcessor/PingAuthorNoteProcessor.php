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
    private const PING_REGEXP = '#@(\S+)#';

    private ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function supports(Comment $comment): bool
    {
        return (bool) preg_match(self::PING_REGEXP, $comment->getNote());
    }

    public function process(Comment $comment): void
    {
        if ($comment->getMergeRequest() === null) {
            return;
        }

        preg_match_all(self::PING_REGEXP, $comment->getNote(), $matches);
        $usernames = $matches[1] ?? [];

        $this->reviewService->notifyAboutPing($comment, $usernames);
    }
}
