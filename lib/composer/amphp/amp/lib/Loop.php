<?php

namespace Amp;

use Amp\Loop\Driver;
use Amp\Loop\DriverFactory;
use Amp\Loop\InvalidWatcherError;
use Amp\Loop\UnsupportedFeatureException;
use Amp\Loop\Watcher;

/**
 * Accessor to allow global access to the event loop.
 *
 * @see \Amp\Loop\Driver
 */
final class Loop
{
    /**
     * @var Driver
     */
    private static $driver;

    /**
     * Disable construction as this is a static class.
     */
    private function __construct()
    {
        // intentionally left blank
    }

    /**
     * Sets the driver to be used for `Loop::run()`.
     *
     * @param Driver $driver
     *
     * @return void
     */
    public static function set(Driver $driver)
    {
        try {
            self::$driver = new class extends Driver {
                /**
                 * @return void
                 */
                protected function activate(array $watchers)
                {
                    throw new \Error("Can't activate watcher during garbage collection.");
                }

                /**
                 * @return void
                 */
                protected function dispatch(bool $blocking)
                {
                    throw new \Error("Can't dispatch during garbage collection.");
                }

                /**
                 * @return void
                 */
                protected function deactivate(Watcher $watcher)
                {
                    // do nothing
                }

                public function getHandle()
                {
                    return null;
                }
            };

            \gc_collect_cycles();
        } finally {
            self::$driver = $driver;
        }
    }

    /**
     * Run the event loop and optionally execute a callback within the scope of it.
     *
     * The loop MUST continue to run until it is either stopped explicitly, no referenced watchers exist anymore, or an
     * exception is thrown that cannot be handled. Exceptions that cannot be handled are exceptions thrown from an
     * error handler or exceptions that would be passed to an error handler but none exists to handle them.
     *
     * @param callable|null $callback The callback to execute.
     *
     * @return void
     */
    public static function run(callable $callback = null)
    {
        if ($callback) {
            self::$driver->defer($callback);
        }

        self::$driver->run();
    }

    /**
     * Stop the event loop.
     *
     * When an event loop is stopped, it continues with its current tick and exits the loop afterwards. Multiple calls
     * to stop MUST be ignored and MUST NOT raise an exception.
     *
     * @return void
     */
    public static function stop()
    {
        self::$driver->stop();
    }

    /**
     * Defer the execution of a callback.
     *
     * The deferred callable MUST be executed before any other type of watcher in a tick. Order of enabling MUST be
     * preserved when executing the callbacks.
     *
     * The created watcher MUST immediately be marked as enabled, but only be activated (i.e. callback can be called)
     * right before the next tick. Callbacks of watchers MUST NOT be called in the tick they were enabled.
     *
     * @param callable(string $watcherId, mixed $data) $callback The callback to defer. The `$watcherId` will be
     *     invalidated before the callback call.
     * @param mixed $data Arbitrary data given to the callback function as the `$data` parameter.
     *
     * @return string An unique identifier that can be used to cancel, enable or disable the watcher.
     */
    public static function defer(callable $callback, $data = null): string
    {
        return self::$driver->defer($callback, $data);
    }

    /**
     * Delay the execution of a callback.
     *
     * The delay is a minimum and approximate, accuracy is not guaranteed. Order of calls MUST be determined by which
     * timers expire first, but timers with the same expiration time MAY be executed in any order.
     *
     * The created watcher MUST immediately be marked as enabled, but only be activated (i.e. callback can be called)
     * right before the next tick. Callbacks of watchers MUST NOT be called in the tick they were enabled.
     *
     * @param int   $delay The amount of time, in milliseconds, to delay the execution for.
     * @param callable(string $watcherId, mixed $data) $callback The callback to delay. The `$watcherId` will be
     *     invalidated before the callback call.
     * @param mixed $data Arbitrary data given to the callback function as the `$data` parameter.
     *
     * @return string An unique identifier that can be used to cancel, enable or disable the watcher.
     */
    public static function delay(int $delay, callable $callback, $data = null): string
    {
        return self::$driver->delay($delay, $callback, $data);
    }

    /**
     * Repeatedly execute a callback.
     *
     * The interval between executions is a minimum and approximate, accuracy is not guaranteed. Order of calls MUST be
     * determined by which timers expire first, but timers with the same expiration time MAY be executed in any order.
     * The first execution is scheduled after the first interval period.
     *
     * The created watcher MUST immediately be marked as enabled, but only be activated (i.e. callback can be called)
     * right before the next tick. Callbacks of watchers MUST NOT be called in the tick they were enabled.
     *
     * @param int   $interval The time interval, in milliseconds, to wait between executions.
     * @param callable(string $watcherId, mixed $data) $callback The callback to repeat.
     * @param mixed $data Arbitrary data given to the callback function as the `$data` parameter.
     *
     * @return string An unique identifier that can be used to cancel, enable or disable the watcher.
     */
    public static function repeat(int $interval, callable $callback, $data = null): string
    {
        return self::$driver->repeat($interval, $callback, $data);
    }

    /**
     * Execute a callback when a stream resource becomes readable or is closed for reading.
     *
     * Warning: Closing resources locally, e.g. with `fclose`, might not invoke the callback. Be sure to `cancel` the
     * watcher when closing the resource locally. Drivers MAY choose to notify the user if there are watchers on invalid
     * resources, but are not required to, due to the high performance impact. Watchers on closed resources are
     * therefore undefined behavior.
     *
     * Multiple watchers on the same stream MAY be executed in any order.
     *
     * The created watcher MUST immediately be marked as enabled, but only be activated (i.e. callback can be called)
     * right before the next tick. Callbacks of watchers MUST NOT be called in the tick they were enabled.
     *
     * @param resource $stream The stream to monitor.
     * @param callable(string $watcherId, resource $stream, mixed $data) $callback The callback to execute.
     * @param mixed    $data Arbitrary data given to the callback function as the `$data` parameter.
     *
     * @return string An unique identifier that can be used to cancel, enable or disable the watcher.
     */
    public static function onReadable($stream, callable $callback, $data = null): string
    {
        return self::$driver->onReadable($stream, $callback, $data);
    }

    /**
     * Execute a callback when a stream resource becomes writable or is closed for writing.
     *
     * Warning: Closing resources locally, e.g. with `fclose`, might not invoke the callback. Be sure to `cancel` the
     * watcher when closing the resource locally. Drivers MAY choose to notify the user if there are watchers on invalid
     * resources, but are not required to, due to the high performance impact. Watchers on closed resources are
     * therefore undefined behavior.
     *
     * Multiple watchers on the same stream MAY be executed in any order.
     *
     * The created watcher MUST immediately be marked as enabled, but only be activated (i.e. callback can be called)
     * right before the next tick. Callbacks of watchers MUST NOT be called in the tick they were enabled.
     *
     * @param resource $stream The stream to monitor.
     * @param callable(string $watcherId, resource $stream, mixed $data) $callback The callback to execute.
     * @param mixed    $data Arbitrary data given to the callback function as the `$data` parameter.
     *
     * @return string An unique identifier that can be used to cancel, enable or disable the watcher.
     */
    public static function onWritable($stream, callable $callback, $data = null): string
    {
        return self::$driver->onWritable($stream, $callback, $data);
    }

    /**
     * Execute a callback when a signal is received.
     *
     * Warning: Installing the same signal on different instances of this interface is deemed undefined behavior.
     * Implementations MAY try to detect this, if possible, but are not required to. This is due to technical
     * limitations of the signals being registered globally per process.
     *
     * Multiple watchers on the same signal MAY be executed in any order.
     *
     * The created watcher MUST immediately be marked as enabled, but only be activated (i.e. callback can be called)
     * right before the next tick. Callbacks of watchers MUST NOT be called in the tick they were enabled.
     *
     * @param int   $signo The signal number to monitor.
     * @param callable(string $watcherId, int $signo, mixed $data) $callback The callback to execute.
     * @param mixed $data Arbitrary data given to the callback function as the $data parameter.
     *
     * @return string An unique identifier that can be used to cancel, enable or disable the watcher.
     *
     * @throws UnsupportedFeatureException If signal handling is not supported.
     */
    public static function onSignal(int $signo, callable $callback, $data = null): string
    {
        return self::$driver->onSignal($signo, $callback, $data);
    }

    /**
     * Enable a watcher to be active starting in the next tick.
     *
     * Watchers MUST immediately be marked as enabled, but only be activated (i.e. callbacks can be called) right before
     * the next tick. Callbacks of watchers MUST NOT be called in the tick they were enabled.
     *
     * @param string $watcherId The watcher identifier.
     *
     * @return void
     *
     * @throws InvalidWatcherError If the watcher identifier is invalid.
     */
    public static function enable(string $watcherId)
    {
        self::$driver->enable($watcherId);
    }

    /**
     * Disable a watcher immediately.
     *
     * A watcher MUST be disabled immediately, e.g. if a defer watcher disables a later defer watcher, the second defer
     * watcher isn't executed in this tick.
     *
     * Disabling a watcher MUST NOT invalidate the watcher. Calling this function MUST NOT fail, even if passed an
     * invalid watcher.
     *
     * @param string $watcherId The watcher identifier.
     *
     * @return void
     */
    public static function disable(string $watcherId)
    {
        if (\PHP_VERSION_ID < 70200 && !isset(self::$driver)) {
            // Prior to PHP 7.2, self::$driver may be unset during destruct.
            // See https://github.com/amphp/amp/issues/212.
            return;
        }

        self::$driver->disable($watcherId);
    }

    /**
     * Cancel a watcher.
     *
     * This will detatch the event loop from all resources that are associated to the watcher. After this operation the
     * watcher is permanently invalid. Calling this function MUST NOT fail, even if passed an invalid watcher.
     *
     * @param string $watcherId The watcher identifier.
     *
     * @return void
     */
    public static function cancel(string $watcherId)
    {
        if (\PHP_VERSION_ID < 70200 && !isset(self::$driver)) {
            // Prior to PHP 7.2, self::$driver may be unset during destruct.
            // See https://github.com/amphp/amp/issues/212.
            return;
        }

        self::$driver->cancel($watcherId);
    }

    /**
     * Reference a watcher.
     *
     * This will keep the event loop alive whilst the watcher is still being monitored. Watchers have this state by
     * default.
     *
     * @param string $watcherId The watcher identifier.
     *
     * @return void
     *
     * @throws InvalidWatcherError If the watcher identifier is invalid.
     */
    public static function reference(string $watcherId)
    {
        self::$driver->reference($watcherId);
    }

    /**
     * Unreference a watcher.
     *
     * The event loop should exit the run method when only unreferenced watchers are still being monitored. Watchers
     * are all referenced by default.
     *
     * @param string $watcherId The watcher identifier.
     *
     * @return void
     */
    public static function unreference(string $watcherId)
    {
        if (\PHP_VERSION_ID < 70200 && !isset(self::$driver)) {
            // Prior to PHP 7.2, self::$driver may be unset during destruct.
            // See https://github.com/amphp/amp/issues/212.
            return;
        }

        self::$driver->unreference($watcherId);
    }

    /**
     * Returns the current loop time in millisecond increments. Note this value does not necessarily correlate to
     * wall-clock time, rather the value returned is meant to be used in relative comparisons to prior values returned
     * by this method (intervals, expiration calculations, etc.) and is only updated once per loop tick.
     *
     * @return int
     */
    public static function now(): int
    {
        return self::$driver->now();
    }

    /**
     * Stores information in the loop bound registry.
     *
     * Stored information is package private. Packages MUST NOT retrieve the stored state of other packages. Packages
     * MUST use their namespace as prefix for keys. They may do so by using `SomeClass::class` as key.
     *
     * If packages want to expose loop bound state to consumers other than the package, they SHOULD provide a dedicated
     * interface for that purpose instead of sharing the storage key.
     *
     * @param string $key The namespaced storage key.
     * @param mixed  $value The value to be stored.
     *
     * @return void
     */
    public static function setState(string $key, $value)
    {
        self::$driver->setState($key, $value);
    }

    /**
     * Gets information stored bound to the loop.
     *
     * Stored information is package private. Packages MUST NOT retrieve the stored state of other packages. Packages
     * MUST use their namespace as prefix for keys. They may do so by using `SomeClass::class` as key.
     *
     * If packages want to expose loop bound state to consumers other than the package, they SHOULD provide a dedicated
     * interface for that purpose instead of sharing the storage key.
     *
     * @param string $key The namespaced storage key.
     *
     * @return mixed The previously stored value or `null` if it doesn't exist.
     */
    public static function getState(string $key)
    {
        return self::$driver->getState($key);
    }

    /**
     * Set a callback to be executed when an error occurs.
     *
     * The callback receives the error as the first and only parameter. The return value of the callback gets ignored.
     * If it can't handle the error, it MUST throw the error. Errors thrown by the callback or during its invocation
     * MUST be thrown into the `run` loop and stop the driver.
     *
     * Subsequent calls to this method will overwrite the previous handler.
     *
     * @param callable(\Throwable $error)|null $callback The callback to execute. `null` will clear the
     *     current handler.
     *
     * @return callable(\Throwable $error)|null The previous handler, `null` if there was none.
     */
    public static function setErrorHandler(callable $callback = null)
    {
        return self::$driver->setErrorHandler($callback);
    }

    /**
     * Retrieve an associative array of information about the event loop driver.
     *
     * The returned array MUST contain the following data describing the driver's currently registered watchers:
     *
     *     [
     *         "defer"            => ["enabled" => int, "disabled" => int],
     *         "delay"            => ["enabled" => int, "disabled" => int],
     *         "repeat"           => ["enabled" => int, "disabled" => int],
     *         "on_readable"      => ["enabled" => int, "disabled" => int],
     *         "on_writable"      => ["enabled" => int, "disabled" => int],
     *         "on_signal"        => ["enabled" => int, "disabled" => int],
     *         "enabled_watchers" => ["referenced" => int, "unreferenced" => int],
     *         "running"          => bool
     *     ];
     *
     * Implementations MAY optionally add more information in the array but at minimum the above `key => value` format
     * MUST always be provided.
     *
     * @return array Statistics about the loop in the described format.
     */
    public static function getInfo(): array
    {
        return self::$driver->getInfo();
    }

    /**
     * Retrieve the event loop driver that is in scope.
     *
     * @return Driver
     */
    public static function get(): Driver
    {
        return self::$driver;
    }
}

// Default factory, don't move this to a file loaded by the composer "files" autoload mechanism, otherwise custom
// implementations might have issues setting a default loop, because it's overridden by us then.

// @codeCoverageIgnoreStart
Loop::set((new DriverFactory)->create());
// @codeCoverageIgnoreEnd
