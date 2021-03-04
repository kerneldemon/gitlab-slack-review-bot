<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\MergeRequest;
use App\Service\CommentService;
use App\Service\MergeRequestService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController
{
    private const OK = 'OK';

    /**
     * @Route("/events/comments", name="app_handle_event_comment")
     */
    public function handleComment(CommentService $commentService, Comment $comment): Response
    {
        $commentService->processNote($comment);

        return new Response(self::OK);
    }

    /**
     * @Route("/events/merge-requests", name="app_handle_event_merge_request")
     */
    public function handleMergeRequest(MergeRequestService $mergeRequestService, MergeRequest $mergeRequest): Response
    {
        $mergeRequestService->processMergeRequest($mergeRequest);

        return new Response(self::OK);
    }
}
