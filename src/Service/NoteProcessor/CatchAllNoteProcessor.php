<?php

declare(strict_types=1);

namespace App\Service\NoteProcessor;

use App\Entity\Comment;
use Psr\Log\LoggerInterface;

class CatchAllNoteProcessor implements NoteProcessorInterface
{
    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function supports(Comment $comment): bool
    {
        return true;
    }

    public function process(Comment $comment): void
    {
        $this->logger->debug('Received comment in catch all', ['comment' => $comment]);
    }
}
