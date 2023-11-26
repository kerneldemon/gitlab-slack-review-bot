<?php

declare(strict_types=1);

namespace App\Service;

use App\Constant\Gitlab\SystemUser;
use App\Entity\Comment;
use App\Service\NoteProcessor\NoteProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

class CommentService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var NoteProcessorInterface[]
     */
    private $noteProcessors;

    public function __construct(
        EntityManagerInterface $entityManager,
        iterable $noteProcessors
    ) {
        $this->entityManager = $entityManager;
        $this->noteProcessors = $noteProcessors;
    }

    public function processNote(Comment $comment): void
    {
        if ($comment->getMergeRequest() === null) {
            return;
        }

        $author = $comment->getAuthor();
        if ($author->getUsername() === SystemUser::NAME) {
            return;
        }

        foreach ($this->noteProcessors as $noteProcessor) {
            if (!$noteProcessor->supports($comment)) {
                continue;
            }

            $noteProcessor->process($comment);
            if ($noteProcessor->preventFurtherProcessing($comment)) {
                break;
            }
        }

        $this->entityManager->flush();
    }
}
