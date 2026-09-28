<?php

/** Real (wall-clock) time */
define('EXCIMER_REAL', 0);

/** CPU time (user and system) consumed by the thread during execution */
define('EXCIMER_CPU', 1);

/**
 * A sampling profiler.
 *
 * Collects a stack trace every time a timer event fires.
 */
class ExcimerProfiler {
	/**
	 * Set the period.
	 *
	 * This will take effect the next time start() is called.
	 *
	 * If this method is not called, the default period of 0.1 seconds
	 * will be used.
	 *
	 * @param float $period The period in seconds
	 */
	public function setPeriod($period) {
	}

	/**
	 * Set the event type. May be either EXCIMER_REAL, for real (wall-clock)
	 * time, or EXCIMER_CPU, for CPU time. The default is EXCIMER_REAL.
	 *
	 * This will take effect the next time start() is called.
	 *
	 * @param int $eventType
	 */
	public function setEventType($eventType) {
	}

	/**
	 * Set the maximum depth of stack trace collection. If this depth is
	 * exceeded, the traversal up the stack will be terminated, so the function
	 * will appear to have no caller.
	 *
	 * By default, there is no limit. If this is called with a depth of zero,
	 * the limit is disabled.
	 *
	 * This will take effect immediately.
	 *
	 * @param int $maxDepth
	 */
	public function setMaxDepth($maxDepth) {
	}

	/**
	 * Set a callback which will be called once the specified number of samples
	 * has been collected.
	 *
	 * When the ExcimerProfiler object is destroyed, the callback will also
	 * be called, unless no samples have been collected.
	 *
	 * The callback will be called with a single argument: the ExcimerLog
	 * object containing the samples. Before the callback is called, a new
	 * ExcimerLog object will be created and registered with the
	 * ExcimerProfiler. So ExcimerProfiler::getLog() should not be used from
	 * the callback, since it will not return the samples.
	 *
	 * @param callable $callback
	 * @param int $maxSamples
	 */
	public function setFlushCallback($callback, $maxSamples) {
	}

	/**
	 * Clear the flush callback. No callback will be called regardless of
	 * how many samples are collected.
	 */
	public function clearFlushCallback() {
	}

	/**
	 * Start the profiler. If the profiler was already running, it will be
	 * stopped and restarted with new options.
	 */
	public function start() {
	}

	/**
	 * Stop the profiler.
	 */
	public function stop() {
	}

	/**
	 * Get the current ExcimerLog object.
	 *
	 * Note that if the profiler is running, the object thus returned may be
	 * modified by a timer event at any time, potentially invalidating your
	 * analysis. Instead, the profiler should be stopped first, or flush()
	 * should be used.
	 *
	 * @return ExcimerLog
	 */
	public function getLog() {
	}

	/**
	 * Create and register a new ExcimerLog object, and return the old
	 * ExcimerLog object.
	 *
	 * This will return all accumulated events to this point, and reset the
	 * log with a new log of zero length.
	 *
	 * @return ExcimerLog
	 */
	public function flush() {
	}
}

/**
 * A collected series of stack traces and some utility methods to aggregate them.
 *
 * ExcimerLog acts as a container for ExcimerLogEntry objects. The Iterator or
 * ArrayAccess interfaces may be used to access them. For example:
 *
 *   foreach ( $profiler->getLog() as $entry ) {
 *      var_dump( $entry->getTrace() );
 *   }
 */
class ExcimerLog implements ArrayAccess, Iterator {
	/**
	 * ExcimerLog is not constructible by user code. Objects of this type
	 * are available via:
	 *   - ExcimerProfiler::getLog()
	 *   - ExcimerProfiler::flush()
	 *   - The callback to ExcimerProfiler::setFlushCallback()
	 */
	final private function __construct() {
	}

	/**
	 * Aggregate the stack traces and convert them to a line-based format
	 * understood by Brendan Gregg's FlameGraph utility. Each stack trace is
	 * represented as a series of function names, separated by semicolons.
	 * After this identifier, there is a single space character, then a number
	 * giving the number of times the stack appeared. Then there is a line
	 * break. This is repeated for each unique stack trace.
	 *
	 * @return string
	 */
	public function formatCollapsed() {
	}

	/**
	 * Produce an array with an element for every function which appears in
	 * the log. The key is a human-readable unique identifier for the function,
	 * method or closure. The value is an associative array with the following
	 * elements:
	 *
	 *   - self: The number of events in which the function itself was running,
	 *     no other userspace function was being called. This includes time
	 *     spent in internal functions that this function called.
	 *   - inclusive: The number of events in which this function appeared
	 *     somewhere in the stack.
	 *
	 * And optionally the following elements, if they are relevant:
	 *
	 *   - file: The filename in which the function appears
	 *   - line: The exact line number at which the first relevant event
	 *     occurred.
	 *   - class: The class name in which the method is defined
	 *   - function: The name of the function or method
	 *   - closure_line: The line number at which the closure was defined
	 *
	 * The event counts in the "self" and "inclusive" fields are adjusted for
	 * overruns. They represent an estimate of the number of profiling periods
	 * in which those functions were present.
	 *
	 * @return array
	 */
	public function aggregateByFunction() {
	}

	/**
	 * Get an array which can be JSON encoded for import into speedscope
	 *
	 * @return array
	 */
	public function getSpeedscopeData() {
	}

	/**
	 * Get the total number of profiling periods represented by this log.
	 *
	 * @return int
	 */
	public function getEventCount() {
	}

	/**
	 * Get the current ExcimerLogEntry object. Part of the Iterator interface.
	 *
	 * @return ExcimerLogEntry|null
	 */
	public function current() {
	}

	/**
	 * Get the current integer key or null. Part of the Iterator interface.
	 *
	 * @return int|null
	 */
	public function key() {
	}

	/**
	 * Advance to the next log entry. Part of the Iterator interface.
	 */
	public function next() {
	}

	/**
	 * Rewind back to the first log entry. Part of the Iterator interface.
	 */
	public function rewind() {
	}

	/**
	 * Check if the current position is valid. Part of the Iterator interface.
	 *
	 * @return bool
	 */
	public function valid() {
	}

	/**
	 * Get the number of log entries contained in this log. This is always less
	 * than or equal to the number returned by getEventCount(), which includes
	 * overruns.
	 *
	 * @return int
	 */
	public function count() {
	}

	/**
	 * Determine whether a log entry exists at the specified array offset.
	 * Part of the ArrayAccess interface.
	 *
	 * @param int $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
	}

	/**
	 * Get the ExcimerLogEntry object at the specified array offset.
	 *
	 * @param int $offset
	 * @return ExcimerLogEntry|null
	 */
	public function offsetGet($offset) {
	}

	/**
	 * This function is included for compliance with the ArrayAccess interface.
	 * It raises a warning and does nothing.
	 *
	 * @param int $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
	}

	/**
	 * This function is included for compliance with the ArrayAccess interface.
	 * It raises a warning and does nothing.
	 *
	 * @param int $offset
	 */
	public function offsetUnset($offset) {
	}
}
