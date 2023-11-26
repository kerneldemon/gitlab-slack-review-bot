<?php

declare(strict_types=1);

namespace App\Service\NoteProcessor;

use App\Entity\Comment;

interface NoteProcessorInterface
{
    public function supports(Comment $comment): bool;

    public function preventFurtherProcessing(Comment $comment): bool;

    public function process(Comment $comment): void;
}
