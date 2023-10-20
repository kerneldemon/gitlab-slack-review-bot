<?php

declare(strict_types=1);

namespace App\Constant\MergeRequest;

use MyCLabs\Enum\Enum;

class Action extends Enum
{
    public const OPEN = 'open';
    public const UPDATE = 'update';
    public const MERGE = 'merge';
}
