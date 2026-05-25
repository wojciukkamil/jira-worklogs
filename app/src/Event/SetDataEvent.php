<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Event;

class SetDataEvent
{
    /**
     * @var Event[]
     */
    private array $events;

    /**
     * @param mixed[] $filters
     */
    public function __construct(
        private readonly \DateTime $start,
        private readonly \DateTime $end,
        private readonly array $filters,
    ) {
        $this->events = [];
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function getEnd(): \DateTime
    {
        return $this->end;
    }

    /**
     * @return mixed[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addEvent(Event $event): self
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getEventCount(): int
    {
        return \count($this->events);
    }
}
