<?php

namespace Amp\Loop;

use Amp\CallableMaker;
use Amp\Coroutine;
use Amp\Promise;
use React\Promise\PromiseInterface as ReactPromise;
use function Amp\Internal\getCurrentTime;
use function Amp\Promise\rethrow;

class NativeDriver extends Driver
{
    use CallableMaker;

    /** @var resource[] */
    private $readStreams = [];

    /** @var Watcher[][] */
    private $readWatchers = [];

    /** @var resource[] */
    private $writeStreams = [];

    /** @var Watcher[][] */
    private $writeWatchers = [];

    /** @var Internal\TimerQueue */
    private $timerQueue;

    /** @var Watcher[][] */
    private $signalWatchers = [];

    /** @var int Internal timestamp for now. */
    private $now;

    /** @var int Loop time offset */
    private $nowOffset;

    /** @var bool */
    private $signalHandling;

    public function __construct()
    {
        $this->timerQueue = new Internal\TimerQueue;
        $this->signalHandling = \extension_loaded("pcntl");
        $this->nowOffset = getCurrentTime();
        $this->now = \random_int(0, $this->nowOffset);
        $this->nowOffset -= $this->now;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Amp\Loop\UnsupportedFeatureException If the pcntl extension is not available.
     */
    public function onSignal(int $signo, callable $callback, $data = null): string
    {
        if (!$this->signalHandling) {
            throw new UnsupportedFeatureException("Signal handling requires the pcntl extension");
        }

        return parent::onSignal($signo, $callback, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function now(): int
    {
        $this->now = getCurrentTime() - $this->nowOffset;

        return $this->now;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandle()
    {
        return null;
    }

    /**
     * @param bool $blocking
     *
     * @return void
     *
     * @throws \Throwable
     */
    protected function dispatch(bool $blocking)
    {
        $this->selectStreams(
            $this->readStreams,
            $this->writeStreams,
            $blocking ? $this->getTimeout() : 0
        );

        $now = $this->now();

        while ($watcher = $this->timerQueue->extract($now)) {
            if ($watcher->type & Watcher::REPEAT) {
                $watcher->enabled = false; // Trick base class into adding to enable queue when calling enable()
                $this->enable($watcher->id);
            } else {
                $this->cancel($watcher->id);
            }

            try {
                // Execute the timer.
                $result = ($watcher->callback)($watcher->id, $watcher->data);

                if ($result === null) {
                    continue;
                }

                if ($result instanceof \Generator) {
                    $result = new Coroutine($result);
                }

                if ($result instanceof Promise || $result instanceof ReactPromise) {
                    rethrow($result);
                }
            } catch (\Throwable $exception) {
                $this->error($exception);
            }
        }

        if ($this->signalHandling) {
            \pcntl_signal_dispatch();
        }
    }

    /**
     * @param resource[] $read
     * @param resource[] $write
     * @param int        $timeout
     *
     * @return void
     */
    private function selectStreams(array $read, array $write, int $timeout)
    {
        $timeout /= self::MILLISEC_PER_SEC;

        if (!empty($read) || !empty($write)) { // Use stream_select() if there are any streams in the loop.
            if ($timeout >= 0) {
                $seconds = (int) $timeout;
                $microseconds = (int) (($timeout - $seconds) * self::MICROSEC_PER_SEC);
            } else {
                $seconds = null;
                $microseconds = null;
            }

            $except = null;

            // Error reporting suppressed since stream_select() emits an E_WARNING if it is interrupted by a signal.
            if (!($result = @\stream_select($read, $write, $except, $seconds, $microseconds))) {
                if ($result === 0) {
                    return;
                }

                $error = \error_get_last();

                if (\strpos($error["message"] ?? '', "unable to select") !== 0) {
                    return;
                }

                $this->error(new \Exception($error["message"] ?? 'Unknown error during stream_select'));
            }

            foreach ($read as $stream) {
                $streamId = (int) $stream;
                if (!isset($this->readWatchers[$streamId])) {
                    continue; // All read watchers disabled.
                }

                foreach ($this->readWatchers[$streamId] as $watcher) {
                    if (!isset($this->readWatchers[$streamId][$watcher->id])) {
                        continue; // Watcher disabled by another IO watcher.
                    }

                    try {
                        $result = ($watcher->callback)($watcher->id, $stream, $watcher->data);

                        if ($result === null) {
                            continue;
                        }

                        if ($result instanceof \Generator) {
                            $result = new Coroutine($result);
                        }

                        if ($result instanceof Promise || $result instanceof ReactPromise) {
                            rethrow($result);
                        }
                    } catch (\Throwable $exception) {
                        $this->error($exception);
                    }
                }
            }

            \assert(\is_array($write)); // See https://github.com/vimeo/psalm/issues/3036

            foreach ($write as $stream) {
                $streamId = (int) $stream;
                if (!isset($this->writeWatchers[$streamId])) {
                    continue; // All write watchers disabled.
                }

                foreach ($this->writeWatchers[$streamId] as $watcher) {
                    if (!isset($this->writeWatchers[$streamId][$watcher->id])) {
                        continue; // Watcher disabled by another IO watcher.
                    }

                    try {
                        $result = ($watcher->callback)($watcher->id, $stream, $watcher->data);

                        if ($result === null) {
                            continue;
                        }

                        if ($result instanceof \Generator) {
                            $result = new Coroutine($result);
                        }

                        if ($result instanceof Promise || $result instanceof ReactPromise) {
                            rethrow($result);
                        }
                    } catch (\Throwable $exception) {
                        $this->error($exception);
                    }
                }
            }

            return;
        }

        if ($timeout > 0) { // Otherwise sleep with usleep() if $timeout > 0.
            \usleep((int) ($timeout * self::MICROSEC_PER_SEC));
        }
    }

    /**
     * @return int Milliseconds until next timer expires or -1 if there are no pending times.
     */
    private function getTimeout(): int
    {
        $expiration = $this->timerQueue->peek();

        if ($expiration === null) {
            return -1;
        }

        $expiration -= getCurrentTime() - $this->nowOffset;

        return $expiration > 0 ? $expiration : 0;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function activate(array $watchers)
    {
        foreach ($watchers as $watcher) {
            switch ($watcher->type) {
                case Watcher::READABLE:
                    \assert(\is_resource($watcher->value));

                    $streamId = (int) $watcher->value;
                    $this->readWatchers[$streamId][$watcher->id] = $watcher;
                    $this->readStreams[$streamId] = $watcher->value;
                    break;

                case Watcher::WRITABLE:
                    \assert(\is_resource($watcher->value));

                    $streamId = (int) $watcher->value;
                    $this->writeWatchers[$streamId][$watcher->id] = $watcher;
                    $this->writeStreams[$streamId] = $watcher->value;
                    break;

                case Watcher::DELAY:
                case Watcher::REPEAT:
                    \assert(\is_int($watcher->value));
                    $this->timerQueue->insert($watcher);
                    break;

                case Watcher::SIGNAL:
                    \assert(\is_int($watcher->value));

                    if (!isset($this->signalWatchers[$watcher->value])) {
                        if (!@\pcntl_signal($watcher->value, $this->callableFromInstanceMethod('handleSignal'))) {
                            $message = "Failed to register signal handler";
                            if ($error = \error_get_last()) {
                                $message .= \sprintf("; Errno: %d; %s", $error["type"], $error["message"]);
                            }
                            throw new \Error($message);
                        }
                    }

                    $this->signalWatchers[$watcher->value][$watcher->id] = $watcher;
                    break;

                default:
                    // @codeCoverageIgnoreStart
                    throw new \Error("Unknown watcher type");
                // @codeCoverageIgnoreEnd
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function deactivate(Watcher $watcher)
    {
        switch ($watcher->type) {
            case Watcher::READABLE:
                $streamId = (int) $watcher->value;
                unset($this->readWatchers[$streamId][$watcher->id]);
                if (empty($this->readWatchers[$streamId])) {
                    unset($this->readWatchers[$streamId], $this->readStreams[$streamId]);
                }
                break;

            case Watcher::WRITABLE:
                $streamId = (int) $watcher->value;
                unset($this->writeWatchers[$streamId][$watcher->id]);
                if (empty($this->writeWatchers[$streamId])) {
                    unset($this->writeWatchers[$streamId], $this->writeStreams[$streamId]);
                }
                break;

            case Watcher::DELAY:
            case Watcher::REPEAT:
                $this->timerQueue->remove($watcher);
                break;

            case Watcher::SIGNAL:
                \assert(\is_int($watcher->value));

                if (isset($this->signalWatchers[$watcher->value])) {
                    unset($this->signalWatchers[$watcher->value][$watcher->id]);

                    if (empty($this->signalWatchers[$watcher->value])) {
                        unset($this->signalWatchers[$watcher->value]);
                        @\pcntl_signal($watcher->value, \SIG_DFL);
                    }
                }
                break;

            default:
                // @codeCoverageIgnoreStart
                throw new \Error("Unknown watcher type");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param int $signo
     *
     * @return void
     */
    private function handleSignal(int $signo)
    {
        foreach ($this->signalWatchers[$signo] as $watcher) {
            if (!isset($this->signalWatchers[$signo][$watcher->id])) {
                continue;
            }

            try {
                $result = ($watcher->callback)($watcher->id, $signo, $watcher->data);

                if ($result === null) {
                    continue;
                }

                if ($result instanceof \Generator) {
                    $result = new Coroutine($result);
                }

                if ($result instanceof Promise || $result instanceof ReactPromise) {
                    rethrow($result);
                }
            } catch (\Throwable $exception) {
                $this->error($exception);
            }
        }
    }
}
