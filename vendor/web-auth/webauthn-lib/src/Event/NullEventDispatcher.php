<?php

declare(strict_types=1);

namespace Webauthn\Event;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
class NullEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        return $event;
    }
}
