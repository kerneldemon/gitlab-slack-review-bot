<?php

declare(strict_types=1);

namespace App\Entity\Event;

class EventObject
{
    private $objectKind;

    public function getObjectKind(): ?string
    {
        return $this->objectKind;
    }

    public function setObjectKind(string $objectKind): self
    {
        $this->objectKind = $objectKind;

        return $this;
    }
}
