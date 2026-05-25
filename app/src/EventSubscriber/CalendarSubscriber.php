<?php

namespace App\EventSubscriber;

use App\Event\SetDataEvent;
use App\Service\WorklogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WorklogService $worklogService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $setDataEvent): void
    {
        $start = $setDataEvent->getStart();
        $end = $setDataEvent->getEnd();
        $filters = $setDataEvent->getFilters();

        foreach ($this->worklogService->getEvents($start, $end, $filters) as $event) {
            $setDataEvent->addEvent($event);
        }
    }
}
