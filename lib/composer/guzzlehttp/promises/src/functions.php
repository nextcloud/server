<?php

namespace GuzzleHttp\Promise;

/**
 * Get the global task queue used for promise resolution.
 *
 * This task queue MUST be run in an event loop in order for promises to be
 * settled asynchronously. It will be automatically run when synchronously
 * waiting on a promise.
 *
 * <code>
 * while ($eventLoop->isRunning()) {
 *     GuzzleHttp\Promise\queue()->run();
 * }
 * </code>
 *
 * @param TaskQueueInterface $assign Optionally specify a new queue instance.
 *
 * @return TaskQueueInterface
 *
 * @deprecated queue will be removed in guzzlehttp/promises:2.0. Use Utils::queue instead.
 */
function queue(TaskQueueInterface $assign = null)
{
    return Utils::queue($assign);
}

/**
 * Adds a function to run in the task queue when it is next `run()` and returns
 * a promise that is fulfilled or rejected with the result.
 *
 * @param callable $task Task function to run.
 *
 * @return PromiseInterface
 *
 * @deprecated task will be removed in guzzlehttp/promises:2.0. Use Utils::task instead.
 */
function task(callable $task)
{
    return Utils::task($task);
}

/**
 * Creates a promise for a value if the value is not a promise.
 *
 * @param mixed $value Promise or value.
 *
 * @return PromiseInterface
 *
 * @deprecated promise_for will be removed in guzzlehttp/promises:2.0. Use Create::promiseFor instead.
 */
function promise_for($value)
{
    return Create::promiseFor($value);
}

/**
 * Creates a rejected promise for a reason if the reason is not a promise. If
 * the provided reason is a promise, then it is returned as-is.
 *
 * @param mixed $reason Promise or reason.
 *
 * @return PromiseInterface
 *
 * @deprecated rejection_for will be removed in guzzlehttp/promises:2.0. Use Create::rejectionFor instead.
 */
function rejection_for($reason)
{
    return Create::rejectionFor($reason);
}

/**
 * Create an exception for a rejected promise value.
 *
 * @param mixed $reason
 *
 * @return \Exception|\Throwable
 *
 * @deprecated exception_for will be removed in guzzlehttp/promises:2.0. Use Create::exceptionFor instead.
 */
function exception_for($reason)
{
    return Create::exceptionFor($reason);
}

/**
 * Returns an iterator for the given value.
 *
 * @param mixed $value
 *
 * @return \Iterator
 *
 * @deprecated iter_for will be removed in guzzlehttp/promises:2.0. Use Create::iterFor instead.
 */
function iter_for($value)
{
    return Create::iterFor($value);
}

/**
 * Synchronously waits on a promise to resolve and returns an inspection state
 * array.
 *
 * Returns a state associative array containing a "state" key mapping to a
 * valid promise state. If the state of the promise is "fulfilled", the array
 * will contain a "value" key mapping to the fulfilled value of the promise. If
 * the promise is rejected, the array will contain a "reason" key mapping to
 * the rejection reason of the promise.
 *
 * @param PromiseInterface $promise Promise or value.
 *
 * @return array
 *
 * @deprecated inspect will be removed in guzzlehttp/promises:2.0. Use Utils::inspect instead.
 */
function inspect(PromiseInterface $promise)
{
    return Utils::inspect($promise);
}

/**
 * Waits on all of the provided promises, but does not unwrap rejected promises
 * as thrown exception.
 *
 * Returns an array of inspection state arrays.
 *
 * @see inspect for the inspection state array format.
 *
 * @param PromiseInterface[] $promises Traversable of promises to wait upon.
 *
 * @return array
 *
 * @deprecated inspect will be removed in guzzlehttp/promises:2.0. Use Utils::inspectAll instead.
 */
function inspect_all($promises)
{
    return Utils::inspectAll($promises);
}

/**
 * Waits on all of the provided promises and returns the fulfilled values.
 *
 * Returns an array that contains the value of each promise (in the same order
 * the promises were provided). An exception is thrown if any of the promises
 * are rejected.
 *
 * @param iterable<PromiseInterface> $promises Iterable of PromiseInterface objects to wait on.
 *
 * @return array
 *
 * @throws \Exception on error
 * @throws \Throwable on error in PHP >=7
 *
 * @deprecated unwrap will be removed in guzzlehttp/promises:2.0. Use Utils::unwrap instead.
 */
function unwrap($promises)
{
    return Utils::unwrap($promises);
}

/**
 * Given an array of promises, return a promise that is fulfilled when all the
 * items in the array are fulfilled.
 *
 * The promise's fulfillment value is an array with fulfillment values at
 * respective positions to the original array. If any promise in the array
 * rejects, the returned promise is rejected with the rejection reason.
 *
 * @param mixed $promises  Promises or values.
 * @param bool  $recursive If true, resolves new promises that might have been added to the stack during its own resolution.
 *
 * @return PromiseInterface
 *
 * @deprecated all will be removed in guzzlehttp/promises:2.0. Use Utils::all instead.
 */
function all($promises, $recursive = false)
{
    return Utils::all($promises, $recursive);
}

/**
 * Initiate a competitive race between multiple promises or values (values will
 * become immediately fulfilled promises).
 *
 * When count amount of promises have been fulfilled, the returned promise is
 * fulfilled with an array that contains the fulfillment values of the winners
 * in order of resolution.
 *
 * This promise is rejected with a {@see AggregateException} if the number of
 * fulfilled promises is less than the desired $count.
 *
 * @param int   $count    Total number of promises.
 * @param mixed $promises Promises or values.
 *
 * @return PromiseInterface
 *
 * @deprecated some will be removed in guzzlehttp/promises:2.0. Use Utils::some instead.
 */
function some($count, $promises)
{
    return Utils::some($count, $promises);
}

/**
 * Like some(), with 1 as count. However, if the promise fulfills, the
 * fulfillment value is not an array of 1 but the value directly.
 *
 * @param mixed $promises Promises or values.
 *
 * @return PromiseInterface
 *
 * @deprecated any will be removed in guzzlehttp/promises:2.0. Use Utils::any instead.
 */
function any($promises)
{
    return Utils::any($promises);
}

/**
 * Returns a promise that is fulfilled when all of the provided promises have
 * been fulfilled or rejected.
 *
 * The returned promise is fulfilled with an array of inspection state arrays.
 *
 * @see inspect for the inspection state array format.
 *
 * @param mixed $promises Promises or values.
 *
 * @return PromiseInterface
 *
 * @deprecated settle will be removed in guzzlehttp/promises:2.0. Use Utils::settle instead.
 */
function settle($promises)
{
    return Utils::settle($promises);
}

/**
 * Given an iterator that yields promises or values, returns a promise that is
 * fulfilled with a null value when the iterator has been consumed or the
 * aggregate promise has been fulfilled or rejected.
 *
 * $onFulfilled is a function that accepts the fulfilled value, iterator index,
 * and the aggregate promise. The callback can invoke any necessary side
 * effects and choose to resolve or reject the aggregate if needed.
 *
 * $onRejected is a function that accepts the rejection reason, iterator index,
 * and the aggregate promise. The callback can invoke any necessary side
 * effects and choose to resolve or reject the aggregate if needed.
 *
 * @param mixed    $iterable    Iterator or array to iterate over.
 * @param callable $onFulfilled
 * @param callable $onRejected
 *
 * @return PromiseInterface
 *
 * @deprecated each will be removed in guzzlehttp/promises:2.0. Use Each::of instead.
 */
function each(
    $iterable,
    callable $onFulfilled = null,
    callable $onRejected = null
) {
    return Each::of($iterable, $onFulfilled, $onRejected);
}

/**
 * Like each, but only allows a certain number of outstanding promises at any
 * given time.
 *
 * $concurrency may be an integer or a function that accepts the number of
 * pending promises and returns a numeric concurrency limit value to allow for
 * dynamic a concurrency size.
 *
 * @param mixed        $iterable
 * @param int|callable $concurrency
 * @param callable     $onFulfilled
 * @param callable     $onRejected
 *
 * @return PromiseInterface
 *
 * @deprecated each_limit will be removed in guzzlehttp/promises:2.0. Use Each::ofLimit instead.
 */
function each_limit(
    $iterable,
    $concurrency,
    callable $onFulfilled = null,
    callable $onRejected = null
) {
    return Each::ofLimit($iterable, $concurrency, $onFulfilled, $onRejected);
}

/**
 * Like each_limit, but ensures that no promise in the given $iterable argument
 * is rejected. If any promise is rejected, then the aggregate promise is
 * rejected with the encountered rejection.
 *
 * @param mixed        $iterable
 * @param int|callable $concurrency
 * @param callable     $onFulfilled
 *
 * @return PromiseInterface
 *
 * @deprecated each_limit_all will be removed in guzzlehttp/promises:2.0. Use Each::ofLimitAll instead.
 */
function each_limit_all(
    $iterable,
    $concurrency,
    callable $onFulfilled = null
) {
    return Each::ofLimitAll($iterable, $concurrency, $onFulfilled);
}

/**
 * Returns true if a promise is fulfilled.
 *
 * @return bool
 *
 * @deprecated is_fulfilled will be removed in guzzlehttp/promises:2.0. Use Is::fulfilled instead.
 */
function is_fulfilled(PromiseInterface $promise)
{
    return Is::fulfilled($promise);
}

/**
 * Returns true if a promise is rejected.
 *
 * @return bool
 *
 * @deprecated is_rejected will be removed in guzzlehttp/promises:2.0. Use Is::rejected instead.
 */
function is_rejected(PromiseInterface $promise)
{
    return Is::rejected($promise);
}

/**
 * Returns true if a promise is fulfilled or rejected.
 *
 * @return bool
 *
 * @deprecated is_settled will be removed in guzzlehttp/promises:2.0. Use Is::settled instead.
 */
function is_settled(PromiseInterface $promise)
{
    return Is::settled($promise);
}

/**
 * Create a new coroutine.
 *
 * @see Coroutine
 *
 * @return PromiseInterface
 *
 * @deprecated coroutine will be removed in guzzlehttp/promises:2.0. Use Coroutine::of instead.
 */
function coroutine(callable $generatorFn)
{
    return Coroutine::of($generatorFn);
}
