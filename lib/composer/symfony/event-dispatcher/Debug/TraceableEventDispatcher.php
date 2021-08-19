<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\EventDispatcher\Debug;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Symfony\Component\EventDispatcher\LegacyEventProxy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\Event as ContractsEvent;

/**
 * Collects some data about event listeners.
 *
 * This event dispatcher delegates the dispatching to another one.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TraceableEventDispatcher implements TraceableEventDispatcherInterface
{
    protected $logger;
    protected $stopwatch;

    private $callStack;
    private $dispatcher;
    private $wrappedListeners;
    private $orphanedEvents;
    private $requestStack;
    private $currentRequestHash = '';

    public function __construct(EventDispatcherInterface $dispatcher, Stopwatch $stopwatch, LoggerInterface $logger = null, RequestStack $requestStack = null)
    {
        $this->dispatcher = LegacyEventDispatcherProxy::decorate($dispatcher);
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
        $this->wrappedListeners = [];
        $this->orphanedEvents = [];
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener($eventName, $listener)
    {
        if (isset($this->wrappedListeners[$eventName])) {
            foreach ($this->wrappedListeners[$eventName] as $index => $wrappedListener) {
                if ($wrappedListener->getWrappedListener() === $listener) {
                    $listener = $wrappedListener;
                    unset($this->wrappedListeners[$eventName][$index]);
                    break;
                }
            }
        }

        return $this->dispatcher->removeListener($eventName, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->dispatcher->removeSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners($eventName = null)
    {
        return $this->dispatcher->getListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function getListenerPriority($eventName, $listener)
    {
        // we might have wrapped listeners for the event (if called while dispatching)
        // in that case get the priority by wrapper
        if (isset($this->wrappedListeners[$eventName])) {
            foreach ($this->wrappedListeners[$eventName] as $index => $wrappedListener) {
                if ($wrappedListener->getWrappedListener() === $listener) {
                    return $this->dispatcher->getListenerPriority($eventName, $wrappedListener);
                }
            }
        }

        return $this->dispatcher->getListenerPriority($eventName, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners($eventName = null)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|null $eventName
     */
    public function dispatch($event/*, string $eventName = null*/)
    {
        if (null === $this->callStack) {
            $this->callStack = new \SplObjectStorage();
        }

        $currentRequestHash = $this->currentRequestHash = $this->requestStack && ($request = $this->requestStack->getCurrentRequest()) ? spl_object_hash($request) : '';
        $eventName = 1 < \func_num_args() ? func_get_arg(1) : null;

        if (\is_object($event)) {
            $eventName = $eventName ?? \get_class($event);
        } else {
            @trigger_error(sprintf('Calling the "%s::dispatch()" method with the event name as first argument is deprecated since Symfony 4.3, pass it second and provide the event object first instead.', EventDispatcherInterface::class), \E_USER_DEPRECATED);
            $swap = $event;
            $event = $eventName ?? new Event();
            $eventName = $swap;

            if (!$event instanceof Event) {
                throw new \TypeError(sprintf('Argument 1 passed to "%s::dispatch()" must be an instance of "%s", "%s" given.', EventDispatcherInterface::class, Event::class, \is_object($event) ? \get_class($event) : \gettype($event)));
            }
        }

        if (null !== $this->logger && ($event instanceof Event || $event instanceof ContractsEvent || $event instanceof StoppableEventInterface) && $event->isPropagationStopped()) {
            $this->logger->debug(sprintf('The "%s" event is already stopped. No listeners have been called.', $eventName));
        }

        $this->preProcess($eventName);
        try {
            $this->beforeDispatch($eventName, $event);
            try {
                $e = $this->stopwatch->start($eventName, 'section');
                try {
                    $this->dispatcher->dispatch($event, $eventName);
                } finally {
                    if ($e->isStarted()) {
                        $e->stop();
                    }
                }
            } finally {
                $this->afterDispatch($eventName, $event);
            }
        } finally {
            $this->currentRequestHash = $currentRequestHash;
            $this->postProcess($eventName);
        }

        return $event;
    }

    /**
     * {@inheritdoc}
     *
     * @param Request|null $request The request to get listeners for
     */
    public function getCalledListeners(/* Request $request = null */)
    {
        if (null === $this->callStack) {
            return [];
        }

        $hash = 1 <= \func_num_args() && null !== ($request = func_get_arg(0)) ? spl_object_hash($request) : null;
        $called = [];
        foreach ($this->callStack as $listener) {
            [$eventName, $requestHash] = $this->callStack->getInfo();
            if (null === $hash || $hash === $requestHash) {
                $called[] = $listener->getInfo($eventName);
            }
        }

        return $called;
    }

    /**
     * {@inheritdoc}
     *
     * @param Request|null $request The request to get listeners for
     */
    public function getNotCalledListeners(/* Request $request = null */)
    {
        try {
            $allListeners = $this->getListeners();
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->info('An exception was thrown while getting the uncalled listeners.', ['exception' => $e]);
            }

            // unable to retrieve the uncalled listeners
            return [];
        }

        $hash = 1 <= \func_num_args() && null !== ($request = func_get_arg(0)) ? spl_object_hash($request) : null;
        $calledListeners = [];

        if (null !== $this->callStack) {
            foreach ($this->callStack as $calledListener) {
                [, $requestHash] = $this->callStack->getInfo();

                if (null === $hash || $hash === $requestHash) {
                    $calledListeners[] = $calledListener->getWrappedListener();
                }
            }
        }

        $notCalled = [];
        foreach ($allListeners as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if (!\in_array($listener, $calledListeners, true)) {
                    if (!$listener instanceof WrappedListener) {
                        $listener = new WrappedListener($listener, null, $this->stopwatch, $this);
                    }
                    $notCalled[] = $listener->getInfo($eventName);
                }
            }
        }

        uasort($notCalled, [$this, 'sortNotCalledListeners']);

        return $notCalled;
    }

    /**
     * @param Request|null $request The request to get orphaned events for
     */
    public function getOrphanedEvents(/* Request $request = null */): array
    {
        if (1 <= \func_num_args() && null !== $request = func_get_arg(0)) {
            return $this->orphanedEvents[spl_object_hash($request)] ?? [];
        }

        if (!$this->orphanedEvents) {
            return [];
        }

        return array_merge(...array_values($this->orphanedEvents));
    }

    public function reset()
    {
        $this->callStack = null;
        $this->orphanedEvents = [];
        $this->currentRequestHash = '';
    }

    /**
     * Proxies all method calls to the original event dispatcher.
     *
     * @param string $method    The method name
     * @param array  $arguments The method arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->dispatcher->{$method}(...$arguments);
    }

    /**
     * Called before dispatching the event.
     *
     * @param object $event
     */
    protected function beforeDispatch(string $eventName, $event)
    {
        $this->preDispatch($eventName, $event instanceof Event ? $event : new LegacyEventProxy($event));
    }

    /**
     * Called after dispatching the event.
     *
     * @param object $event
     */
    protected function afterDispatch(string $eventName, $event)
    {
        $this->postDispatch($eventName, $event instanceof Event ? $event : new LegacyEventProxy($event));
    }

    /**
     * @deprecated since Symfony 4.3, will be removed in 5.0, use beforeDispatch instead
     */
    protected function preDispatch($eventName, Event $event)
    {
    }

    /**
     * @deprecated since Symfony 4.3, will be removed in 5.0, use afterDispatch instead
     */
    protected function postDispatch($eventName, Event $event)
    {
    }

    private function preProcess(string $eventName)
    {
        if (!$this->dispatcher->hasListeners($eventName)) {
            $this->orphanedEvents[$this->currentRequestHash][] = $eventName;

            return;
        }

        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            $priority = $this->getListenerPriority($eventName, $listener);
            $wrappedListener = new WrappedListener($listener instanceof WrappedListener ? $listener->getWrappedListener() : $listener, null, $this->stopwatch, $this);
            $this->wrappedListeners[$eventName][] = $wrappedListener;
            $this->dispatcher->removeListener($eventName, $listener);
            $this->dispatcher->addListener($eventName, $wrappedListener, $priority);
            $this->callStack->attach($wrappedListener, [$eventName, $this->currentRequestHash]);
        }
    }

    private function postProcess(string $eventName)
    {
        unset($this->wrappedListeners[$eventName]);
        $skipped = false;
        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            if (!$listener instanceof WrappedListener) { // #12845: a new listener was added during dispatch.
                continue;
            }
            // Unwrap listener
            $priority = $this->getListenerPriority($eventName, $listener);
            $this->dispatcher->removeListener($eventName, $listener);
            $this->dispatcher->addListener($eventName, $listener->getWrappedListener(), $priority);

            if (null !== $this->logger) {
                $context = ['event' => $eventName, 'listener' => $listener->getPretty()];
            }

            if ($listener->wasCalled()) {
                if (null !== $this->logger) {
                    $this->logger->debug('Notified event "{event}" to listener "{listener}".', $context);
                }
            } else {
                $this->callStack->detach($listener);
            }

            if (null !== $this->logger && $skipped) {
                $this->logger->debug('Listener "{listener}" was not called for event "{event}".', $context);
            }

            if ($listener->stoppedPropagation()) {
                if (null !== $this->logger) {
                    $this->logger->debug('Listener "{listener}" stopped propagation of the event "{event}".', $context);
                }

                $skipped = true;
            }
        }
    }

    private function sortNotCalledListeners(array $a, array $b)
    {
        if (0 !== $cmp = strcmp($a['event'], $b['event'])) {
            return $cmp;
        }

        if (\is_int($a['priority']) && !\is_int($b['priority'])) {
            return 1;
        }

        if (!\is_int($a['priority']) && \is_int($b['priority'])) {
            return -1;
        }

        if ($a['priority'] === $b['priority']) {
            return 0;
        }

        if ($a['priority'] > $b['priority']) {
            return -1;
        }

        return 1;
    }
}
