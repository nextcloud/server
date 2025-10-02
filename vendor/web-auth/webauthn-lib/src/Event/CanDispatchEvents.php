<?php

declare(strict_types=1);

namespace Webauthn\Event;

use Psr\EventDispatcher\EventDispatcherInterface;

interface CanDispatchEvents
{
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void;
}
