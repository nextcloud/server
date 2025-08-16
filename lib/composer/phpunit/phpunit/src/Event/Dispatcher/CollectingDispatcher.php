<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Event;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class CollectingDispatcher implements Dispatcher
{
    private EventCollection $events;

    public function __construct()
    {
        $this->events = new EventCollection;
    }

    public function dispatch(Event $event): void
    {
        $this->events->add($event);
    }

    public function flush(): EventCollection
    {
        $events = $this->events;

        $this->events = new EventCollection;

        return $events;
    }
}
