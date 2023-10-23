<?php

declare(strict_types=1);

namespace App\Constant\MergeRequest;

use MyCLabs\Enum\Enum;

class State extends Enum
{
    public const MERGED = 'merged';
    public const OPENED = 'opened';
}
