<?php
declare(strict_types=1);

namespace App\Service\NoteProcessor;

use App\Entity\Comment;

abstract class AbstractNoteProcessor
{
    public function preventFurtherProcessing(Comment $comment): bool
    {
        return true;
    }
}