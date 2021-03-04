<?php

declare(strict_types=1);

namespace App\Constant\Review;

use MyCLabs\Enum\Enum;

class Status extends Enum
{
    public const NEW = 'new';
    public const IN_REVIEW = 'in_review';
    public const COMPLETED = 'completed';
    public const CLOSED = 'closed';
}
