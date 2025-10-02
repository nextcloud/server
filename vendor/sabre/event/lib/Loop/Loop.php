<?php

declare(strict_types=1);

namespace Sabre\Event\Loop;

/**
 * A simple eventloop implementation.
 *
 * This eventloop supports:
 *   * nextTick
 *   * setTimeout for delayed functions
 *   * setInterval for repeating functions
 *   * stream events using stream_select
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Loop
{
    /**
     * Executes a function after x seconds.
     */
    public function setTimeout(callable $cb, float $timeout)
    {
        $triggerTime = microtime(true) + $timeout;

        if (!$this->timers) {
            // Special case when the timers array was empty.
            $this->timers[] = [$triggerTime, $cb];

            return;
        }

        // We need to insert these values in the timers array, but the timers
        // array must be in reverse-order of trigger times.
        //
        // So here we search the array for the insertion point.
        $index = count($this->timers) - 1;
        while (true) {
            if ($triggerTime < $this->timers[$index][0]) {
                array_splice(
                    $this->timers,
                    $index + 1,
                    0,
                    [[$triggerTime, $cb]]
                );
                break;
            } elseif (0 === $index) {
                array_unshift($this->timers, [$triggerTime, $cb]);
                break;
            }
            --$index;
        }
    }

    /**
     * Executes a function every x seconds.
     *
     * The value this function returns can be used to stop the interval with
     * clearInterval.
     */
    public function setInterval(callable $cb, float $timeout): array
    {
        $keepGoing = true;
        $f = null;

        $f = function () use ($cb, &$f, $timeout, &$keepGoing) {
            if ($keepGoing) {
                $cb();
                $this->setTimeout($f, $timeout);
            }
        };
        $this->setTimeout($f, $timeout);

        // Really the only thing that matters is returning the $keepGoing
        // boolean value.
        //
        // We need to pack it in an array to allow returning by reference.
        // Because I'm worried people will be confused by using a boolean as a
        // sort of identifier, I added an extra string.
        return ['I\'m an implementation detail', &$keepGoing];
    }

    /**
     * Stops a running interval.
     */
    public function clearInterval(array $intervalId)
    {
        $intervalId[1] = false;
    }

    /**
     * Runs a function immediately at the next iteration of the loop.
     */
    public function nextTick(callable $cb)
    {
        $this->nextTick[] = $cb;
    }

    /**
     * Adds a read stream.
     *
     * The callback will be called as soon as there is something to read from
     * the stream.
     *
     * You MUST call removeReadStream after you are done with the stream, to
     * prevent the eventloop from never stopping.
     *
     * @param resource $stream
     */
    public function addReadStream($stream, callable $cb)
    {
        $this->readStreams[(int) $stream] = $stream;
        $this->readCallbacks[(int) $stream] = $cb;
    }

    /**
     * Adds a write stream.
     *
     * The callback will be called as soon as the system reports it's ready to
     * receive writes on the stream.
     *
     * You MUST call removeWriteStream after you are done with the stream, to
     * prevent the eventloop from never stopping.
     *
     * @param resource $stream
     */
    public function addWriteStream($stream, callable $cb)
    {
        $this->writeStreams[(int) $stream] = $stream;
        $this->writeCallbacks[(int) $stream] = $cb;
    }

    /**
     * Stop watching a stream for reads.
     *
     * @param resource $stream
     */
    public function removeReadStream($stream)
    {
        unset(
            $this->readStreams[(int) $stream],
            $this->readCallbacks[(int) $stream]
        );
    }

    /**
     * Stop watching a stream for writes.
     *
     * @param resource $stream
     */
    public function removeWriteStream($stream)
    {
        unset(
            $this->writeStreams[(int) $stream],
            $this->writeCallbacks[(int) $stream]
        );
    }

    /**
     * Runs the loop.
     *
     * This function will run continuously, until there's no more events to
     * handle.
     */
    public function run()
    {
        $this->running = true;

        do {
            $hasEvents = $this->tick(true);
        } while ($this->running && $hasEvents);
        $this->running = false;
    }

    /**
     * Executes all pending events.
     *
     * If $block is turned true, this function will block until any event is
     * triggered.
     *
     * If there are now timeouts, nextTick callbacks or events in the loop at
     * all, this function will exit immediately.
     *
     * This function will return true if there are _any_ events left in the
     * loop after the tick.
     */
    public function tick(bool $block = false): bool
    {
        $this->runNextTicks();
        $nextTimeout = $this->runTimers();

        // Calculating how long runStreams should at most wait.
        if (!$block) {
            // Don't wait
            $streamWait = 0;
        } elseif ($this->nextTick) {
            // There's a pending 'nextTick'. Don't wait.
            $streamWait = 0;
        } elseif (is_numeric($nextTimeout)) {
            // Wait until the next Timeout should trigger.
            $streamWait = $nextTimeout;
        } else {
            // Wait indefinitely
            $streamWait = null;
        }

        $this->runStreams($streamWait);

        return $this->readStreams || $this->writeStreams || $this->nextTick || $this->timers;
    }

    /**
     * Stops a running eventloop.
     */
    public function stop()
    {
        $this->running = false;
    }

    /**
     * Executes all 'nextTick' callbacks.
     *
     * return void
     */
    protected function runNextTicks()
    {
        $nextTick = $this->nextTick;
        $this->nextTick = [];

        foreach ($nextTick as $cb) {
            $cb();
        }
    }

    /**
     * Runs all pending timers.
     *
     * After running the timer callbacks, this function returns the number of
     * seconds until the next timer should be executed.
     *
     * If there's no more pending timers, this function returns null.
     *
     * @return float|null
     */
    protected function runTimers()
    {
        $now = microtime(true);
        while (($timer = array_pop($this->timers)) && $timer[0] < $now) {
            $timer[1]();
        }
        // Add the last timer back to the array.
        if ($timer) {
            $this->timers[] = $timer;

            return max(0, $timer[0] - microtime(true));
        }
    }

    /**
     * Runs all pending stream events.
     *
     * If $timeout is 0, it will return immediately. If $timeout is null, it
     * will wait indefinitely.
     *
     * @param float|null $timeout
     */
    protected function runStreams($timeout)
    {
        if ($this->readStreams || $this->writeStreams) {
            $read = $this->readStreams;
            $write = $this->writeStreams;
            $except = null;
            // stream_select changes behavior in 8.1 to forbid passing non-null microseconds when the seconds are null.
            // Older versions of php don't allow passing null to microseconds.
            if (null !== $timeout ? stream_select($read, $write, $except, 0, (int) ($timeout * 1000000)) : stream_select($read, $write, $except, null)) {
                // See PHP Bug https://bugs.php.net/bug.php?id=62452
                // Fixed in PHP7
                foreach ($read as $readStream) {
                    $readCb = $this->readCallbacks[(int) $readStream];
                    $readCb();
                }
                foreach ($write as $writeStream) {
                    $writeCb = $this->writeCallbacks[(int) $writeStream];
                    $writeCb();
                }
            }
        } elseif ($this->running && ($this->nextTick || $this->timers)) {
            usleep(null !== $timeout ? intval($timeout * 1000000) : 200000);
        }
    }

    /**
     * Is the main loop active.
     *
     * @var bool
     */
    protected $running = false;

    /**
     * A list of timers, added by setTimeout.
     *
     * @var array
     */
    protected $timers = [];

    /**
     * A list of 'nextTick' callbacks.
     *
     * @var callable[]
     */
    protected $nextTick = [];

    /**
     * List of readable streams for stream_select, indexed by stream id.
     *
     * @var resource[]
     */
    protected $readStreams = [];

    /**
     * List of writable streams for stream_select, indexed by stream id.
     *
     * @var resource[]
     */
    protected $writeStreams = [];

    /**
     * List of read callbacks, indexed by stream id.
     *
     * @var callable[]
     */
    protected $readCallbacks = [];

    /**
     * List of write callbacks, indexed by stream id.
     *
     * @var callable[]
     */
    protected $writeCallbacks = [];
}
