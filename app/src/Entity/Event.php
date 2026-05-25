<?php

declare(strict_types=1);

namespace App\Entity;

class Event
{
    protected bool $allDay = true;

    /**
     * @param mixed[] $options
     */
    public function __construct(
        protected string $title,
        protected \DateTime $start,
        protected ?\DateTime $end = null,
        protected array $options = [],
        protected string $spendTime = '1h',
        protected string $message = '',
    ) {
        $this->setEnd($end);
        $this->setStart($start);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start): self
    {
        if ($this->allDay) {
            $start = clone $start;
            $start->setTime(0, 0, 0, 0);
        }
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function setEnd(?\DateTime $end): self
    {
        $this->allDay = null === $end;
        $this->end = $end;

        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): self
    {
        $this->allDay = $allDay;
        if ($allDay) {
            $this->start = clone $this->start;
            $this->start->setTime(0, 0, 0, 0);
        }

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param mixed[] $options
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function addOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function removeOption(string $name): mixed
    {
        if (!isset($this->options[$name])) {
            return null;
        }

        $removed = $this->options[$name];
        unset($this->options[$name]);

        return $removed;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $event = [
            'title' => $this->title,
            'start' => $this->start->format(\DateTime::ATOM),
            'allDay' => $this->allDay,
        ];

        if (null !== $this->end) {
            $event['end'] = $this->end->format(\DateTime::ATOM);
        }

        return $event + $this->options;
    }

        /**
         * Get the value of spendTime
         *
         * @return string
         */
        public function getSpendTime(): string
        {
                return $this->spendTime;
        }

        /**
         * Set the value of spendTime
         *
         * @param string $spendTime
         *
         * @return self
         */
        public function setSpendTime(string $spendTime): self
        {
                $this->spendTime = $spendTime;

                return $this;
        }

        /**
         * Get the value of message
         *
         * @return string
         */
        public function getMessage(): string
        {
                return $this->message;
        }

        /**
         * Set the value of message
         *
         * @param string $message
         *
         * @return self
         */
        public function setMessage(string $message): self
        {
                $this->message = $message;

                return $this;
        }
}
