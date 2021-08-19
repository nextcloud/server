<?php

namespace Safe;

use Safe\Exceptions\LibeventException;

/**
 * event_add schedules the execution of the event
 * when the event specified in event_set occurs or in at least the time
 * specified by the timeout argument. If
 * timeout was not specified, not timeout is set. The
 * event must be already initalized by event_set
 * and event_base_set functions. If the
 * event already has a timeout set, it is replaced by
 * the new one.
 *
 * @param resource $event Valid event resource.
 * @param int $timeout Optional timeout (in microseconds).
 * @throws LibeventException
 *
 */
function event_add($event, int $timeout = -1): void
{
    error_clear_last();
    $result = \event_add($event, $timeout);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Abort the active event loop immediately. The behaviour is similar to
 * break statement.
 *
 * @param resource $event_base Valid event base resource.
 * @throws LibeventException
 *
 */
function event_base_loopbreak($event_base): void
{
    error_clear_last();
    $result = \event_base_loopbreak($event_base);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * The next event loop iteration after the given timer expires will complete
 * normally, then exit without blocking for events again.
 *
 * @param resource $event_base Valid event base resource.
 * @param int $timeout Optional timeout parameter (in microseconds).
 * @throws LibeventException
 *
 */
function event_base_loopexit($event_base, int $timeout = -1): void
{
    error_clear_last();
    $result = \event_base_loopexit($event_base, $timeout);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Returns new event base, which can be used later in event_base_set,
 * event_base_loop and other functions.
 *
 * @return resource event_base_new returns valid event base resource on
 * success.
 * @throws LibeventException
 *
 */
function event_base_new()
{
    error_clear_last();
    $result = \event_base_new();
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets the number of different event priority levels.
 *
 * By default all events are scheduled with the same priority
 * (npriorities/2).
 * Using event_base_priority_init you can change the number
 * of event priority levels and then set a desired priority for each event.
 *
 * @param resource $event_base Valid event base resource.
 * @param int $npriorities The number of event priority levels.
 * @throws LibeventException
 *
 */
function event_base_priority_init($event_base, int $npriorities): void
{
    error_clear_last();
    $result = \event_base_priority_init($event_base, $npriorities);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Some event mechanisms do not survive across fork. The
 * event_base needs to be reinitialized with this
 * function.
 *
 * @param resource $event_base Valid event base resource that needs to be re-initialized.
 * @throws LibeventException
 *
 */
function event_base_reinit($event_base): void
{
    error_clear_last();
    $result = \event_base_reinit($event_base);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Associates the event_base with the
 * event.
 *
 * @param resource $event Valid event resource.
 * @param resource $event_base Valid event base resource.
 * @throws LibeventException
 *
 */
function event_base_set($event, $event_base): void
{
    error_clear_last();
    $result = \event_base_set($event, $event_base);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Assign the specified bevent to the
 * event_base.
 *
 * @param resource $bevent Valid buffered event resource.
 * @param resource $event_base Valid event base resource.
 * @throws LibeventException
 *
 */
function event_buffer_base_set($bevent, $event_base): void
{
    error_clear_last();
    $result = \event_buffer_base_set($bevent, $event_base);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Disables the specified buffered event.
 *
 * @param resource $bevent Valid buffered event resource.
 * @param int $events Any combination of EV_READ and
 * EV_WRITE.
 * @throws LibeventException
 *
 */
function event_buffer_disable($bevent, int $events): void
{
    error_clear_last();
    $result = \event_buffer_disable($bevent, $events);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Enables the specified buffered event.
 *
 * @param resource $bevent Valid buffered event resource.
 * @param int $events Any combination of EV_READ and
 * EV_WRITE.
 * @throws LibeventException
 *
 */
function event_buffer_enable($bevent, int $events): void
{
    error_clear_last();
    $result = \event_buffer_enable($bevent, $events);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Libevent provides an abstraction layer on top of the regular event API.
 * Using buffered event you don't need to deal with the I/O manually, instead
 * it provides input and output buffers that get filled and drained
 * automatically.
 *
 * @param resource $stream Valid PHP stream resource. Must be castable to file descriptor.
 * @param mixed $readcb Callback to invoke where there is data to read, or NULL if
 * no callback is desired.
 * @param mixed $writecb Callback to invoke where the descriptor is ready for writing,
 * or NULL if no callback is desired.
 * @param mixed $errorcb Callback to invoke where there is an error on the descriptor, cannot be
 * NULL.
 * @param mixed $arg An argument that will be passed to each of the callbacks (optional).
 * @return resource event_buffer_new returns new buffered event resource
 * on success.
 * @throws LibeventException
 *
 */
function event_buffer_new($stream, $readcb, $writecb, $errorcb, $arg = null)
{
    error_clear_last();
    if ($arg !== null) {
        $result = \event_buffer_new($stream, $readcb, $writecb, $errorcb, $arg);
    } else {
        $result = \event_buffer_new($stream, $readcb, $writecb, $errorcb);
    }
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
    return $result;
}


/**
 * Assign a priority to the bevent.
 *
 * @param resource $bevent Valid buffered event resource.
 * @param int $priority Priority level. Cannot be less than zero and cannot exceed maximum
 * priority level of the event base (see event_base_priority_init).
 * @throws LibeventException
 *
 */
function event_buffer_priority_set($bevent, int $priority): void
{
    error_clear_last();
    $result = \event_buffer_priority_set($bevent, $priority);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Sets or changes existing callbacks for the buffered event.
 *
 * @param resource $event Valid buffered event resource.
 * @param mixed $readcb Callback to invoke where there is data to read, or NULL if
 * no callback is desired.
 * @param mixed $writecb Callback to invoke where the descriptor is ready for writing,
 * or NULL if no callback is desired.
 * @param mixed $errorcb Callback to invoke where there is an error on the descriptor, cannot be
 * NULL.
 * @param mixed $arg An argument that will be passed to each of the callbacks (optional).
 * @throws LibeventException
 *
 */
function event_buffer_set_callback($event, $readcb, $writecb, $errorcb, $arg = null): void
{
    error_clear_last();
    if ($arg !== null) {
        $result = \event_buffer_set_callback($event, $readcb, $writecb, $errorcb, $arg);
    } else {
        $result = \event_buffer_set_callback($event, $readcb, $writecb, $errorcb);
    }
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Writes data to the specified buffered event. The data is appended to the
 * output buffer and written to the descriptor when it becomes available for
 * writing.
 *
 * @param resource $bevent Valid buffered event resource.
 * @param string $data The data to be written.
 * @param int $data_size Optional size parameter. event_buffer_write writes
 * all the data by default.
 * @throws LibeventException
 *
 */
function event_buffer_write($bevent, string $data, int $data_size = -1): void
{
    error_clear_last();
    $result = \event_buffer_write($bevent, $data, $data_size);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Cancels the event.
 *
 * @param resource $event Valid event resource.
 * @throws LibeventException
 *
 */
function event_del($event): void
{
    error_clear_last();
    $result = \event_del($event);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Creates and returns a new event resource.
 *
 * @return resource event_new returns a new event resource on success.
 * @throws LibeventException
 *
 */
function event_new()
{
    error_clear_last();
    $result = \event_new();
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
    return $result;
}


/**
 * Assign a priority to the event.
 *
 * @param resource $event Valid event resource.
 * @param int $priority Priority level. Cannot be less than zero and cannot exceed maximum
 * priority level of the event base (see
 * event_base_priority_init).
 * @throws LibeventException
 *
 */
function event_priority_set($event, int $priority): void
{
    error_clear_last();
    $result = \event_priority_set($event, $priority);
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Prepares the event to be used in event_add. The event
 * is prepared to call the function specified by the callback
 * on the events specified in parameter events, which
 * is a set of the following flags: EV_TIMEOUT,
 * EV_SIGNAL, EV_READ,
 * EV_WRITE and EV_PERSIST.
 *
 * If EV_SIGNAL bit is set in parameter events,
 * the fd is interpreted as signal number.
 *
 * After initializing the event, use event_base_set to
 * associate the event with its event base.
 *
 * In case of matching event, these three arguments are passed to the
 * callback function:
 *
 *
 * fd
 *
 *
 * Signal number or resource indicating the stream.
 *
 *
 *
 *
 * events
 *
 *
 * A flag indicating the event. Consists of the following flags:
 * EV_TIMEOUT, EV_SIGNAL,
 * EV_READ, EV_WRITE
 * and EV_PERSIST.
 *
 *
 *
 *
 * arg
 *
 *
 * Optional parameter, previously passed to event_set
 * as arg.
 *
 *
 *
 *
 *
 * @param resource $event Valid event resource.
 * @param mixed $fd Valid PHP stream resource. The stream must be castable to file
 * descriptor, so you most likely won't be able to use any of filtered
 * streams.
 * @param int $events A set of flags indicating the desired event, can be
 * EV_READ and/or EV_WRITE.
 * The additional flag EV_PERSIST makes the event
 * to persist until event_del is called, otherwise
 * the callback is invoked only once.
 * @param mixed $callback Callback function to be called when the matching event occurs.
 * @param mixed $arg Optional callback parameter.
 * @throws LibeventException
 *
 */
function event_set($event, $fd, int $events, $callback, $arg = null): void
{
    error_clear_last();
    if ($arg !== null) {
        $result = \event_set($event, $fd, $events, $callback, $arg);
    } else {
        $result = \event_set($event, $fd, $events, $callback);
    }
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}


/**
 * Prepares the timer event to be used in event_add. The
 * event is prepared to call the function specified by the
 * callback when the event timeout elapses.
 *
 * After initializing the event, use event_base_set to
 * associate the event with its event base.
 *
 * In case of matching event, these three arguments are passed to the
 * callback function:
 *
 *
 * fd
 *
 *
 * Signal number or resource indicating the stream.
 *
 *
 *
 *
 * events
 *
 *
 * A flag indicating the event. This will always be
 * EV_TIMEOUT for timer events.
 *
 *
 *
 *
 * arg
 *
 *
 * Optional parameter, previously passed to
 * event_timer_set as arg.
 *
 *
 *
 *
 *
 * @param resource $event Valid event resource.
 * @param callable $callback Callback function to be called when the matching event occurs.
 * @param mixed $arg Optional callback parameter.
 * @throws LibeventException
 *
 */
function event_timer_set($event, callable $callback, $arg = null): void
{
    error_clear_last();
    if ($arg !== null) {
        $result = \event_timer_set($event, $callback, $arg);
    } else {
        $result = \event_timer_set($event, $callback);
    }
    if ($result === false) {
        throw LibeventException::createFromPhpError();
    }
}
