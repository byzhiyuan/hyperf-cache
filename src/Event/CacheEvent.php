<?php

declare(strict_types=1);

namespace BY\HyperfCache\Event;


class CacheEvent
{
    public $listener;

    public $arguments;

    public function __construct(string $listener, array $arguments = [])
    {
        $this->listener = $listener;

        $this->arguments = $arguments;
    }
}
