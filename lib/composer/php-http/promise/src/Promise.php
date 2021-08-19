<?php

namespace Http\Promise;

/**
 * Promise represents a value that may not be available yet, but will be resolved at some point in future.
 * It acts like a proxy to the actual value.
 *
 * This interface is an extension of the promises/a+ specification.
 *
 * @see https://promisesaplus.com/
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface Promise
{
    /**
     * Promise has not been fulfilled or rejected.
     */
    const PENDING = 'pending';

    /**
     * Promise has been fulfilled.
     */
    const FULFILLED = 'fulfilled';

    /**
     * Promise has been rejected.
     */
    const REJECTED = 'rejected';

    /**
     * Adds behavior for when the promise is resolved or rejected (response will be available, or error happens).
     *
     * If you do not care about one of the cases, you can set the corresponding callable to null
     * The callback will be called when the value arrived and never more than once.
     *
     * @param callable|null $onFulfilled called when a response will be available
     * @param callable|null $onRejected  called when an exception occurs
     *
     * @return Promise a new resolved promise with value of the executed callback (onFulfilled / onRejected)
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null);

    /**
     * Returns the state of the promise, one of PENDING, FULFILLED or REJECTED.
     *
     * @return string
     */
    public function getState();

    /**
     * Wait for the promise to be fulfilled or rejected.
     *
     * When this method returns, the request has been resolved and if callables have been
     * specified, the appropriate one has terminated.
     *
     * When $unwrap is true (the default), the response is returned, or the exception thrown
     * on failure. Otherwise, nothing is returned or thrown.
     *
     * @param bool $unwrap Whether to return resolved value / throw reason or not
     *
     * @return mixed Resolved value, null if $unwrap is set to false
     *
     * @throws \Exception the rejection reason if $unwrap is set to true and the request failed
     */
    public function wait($unwrap = true);
}
