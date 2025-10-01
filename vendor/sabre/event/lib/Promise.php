<?php

declare(strict_types=1);

namespace Sabre\Event;

use Exception;

/**
 * An implementation of the Promise pattern.
 *
 * A promise represents the result of an asynchronous operation.
 * At any given point a promise can be in one of three states:
 *
 * 1. Pending (the promise does not have a result yet).
 * 2. Fulfilled (the asynchronous operation has completed with a result).
 * 3. Rejected (the asynchronous operation has completed with an error).
 *
 * To get a callback when the operation has finished, use the `then` method.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 *
 * @psalm-template TReturn
 */
class Promise
{
    /**
     * The asynchronous operation is pending.
     */
    public const PENDING = 0;

    /**
     * The asynchronous operation has completed, and has a result.
     */
    public const FULFILLED = 1;

    /**
     * The asynchronous operation has completed with an error.
     */
    public const REJECTED = 2;

    /**
     * The current state of this promise.
     *
     * @var int
     */
    public $state = self::PENDING;

    /**
     * Creates the promise.
     *
     * The passed argument is the executor. The executor is automatically
     * called with two arguments.
     *
     * Each are callbacks that map to $this->fulfill and $this->reject.
     * Using the executor is optional.
     */
    public function __construct(?callable $executor = null)
    {
        if ($executor) {
            $executor(
                [$this, 'fulfill'],
                [$this, 'reject']
            );
        }
    }

    /**
     * This method allows you to specify the callback that will be called after
     * the promise has been fulfilled or rejected.
     *
     * Both arguments are optional.
     *
     * This method returns a new promise, which can be used for chaining.
     * If either the onFulfilled or onRejected callback is called, you may
     * return a result from this callback.
     *
     * If the result of this callback is yet another promise, the result of
     * _that_ promise will be used to set the result of the returned promise.
     *
     * If either of the callbacks return any other value, the returned promise
     * is automatically fulfilled with that value.
     *
     * If either of the callbacks throw an exception, the returned promise will
     * be rejected and the exception will be passed back.
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        // This new subPromise will be returned from this function, and will
        // be fulfilled with the result of the onFulfilled or onRejected event
        // handlers.
        $subPromise = new self();

        switch ($this->state) {
            case self::PENDING:
                // The operation is pending, so we keep a reference to the
                // event handlers so we can call them later.
                $this->subscribers[] = [$subPromise, $onFulfilled, $onRejected];
                break;
            case self::FULFILLED:
                // The async operation is already fulfilled, so we trigger the
                // onFulfilled callback asap.
                $this->invokeCallback($subPromise, $onFulfilled);
                break;
            case self::REJECTED:
                // The async operation failed, so we call the onRejected
                // callback asap.
                $this->invokeCallback($subPromise, $onRejected);
                break;
        }

        return $subPromise;
    }

    /**
     * Add a callback for when this promise is rejected.
     *
     * Its usage is identical to then(). However, the otherwise() function is
     * preferred.
     */
    public function otherwise(callable $onRejected): Promise
    {
        return $this->then(null, $onRejected);
    }

    /**
     * Marks this promise as fulfilled and sets its return value.
     */
    public function fulfill($value = null)
    {
        if (self::PENDING !== $this->state) {
            throw new PromiseAlreadyResolvedException('This promise is already resolved, and you\'re not allowed to resolve a promise more than once');
        }
        $this->state = self::FULFILLED;
        $this->value = $value;
        foreach ($this->subscribers as $subscriber) {
            $this->invokeCallback($subscriber[0], $subscriber[1]);
        }
    }

    /**
     * Marks this promise as rejected, and set its rejection reason.
     */
    public function reject(\Throwable $reason)
    {
        if (self::PENDING !== $this->state) {
            throw new PromiseAlreadyResolvedException('This promise is already resolved, and you\'re not allowed to resolve a promise more than once');
        }
        $this->state = self::REJECTED;
        $this->value = $reason;
        foreach ($this->subscribers as $subscriber) {
            $this->invokeCallback($subscriber[0], $subscriber[2]);
        }
    }

    /**
     * Stops execution until this promise is resolved.
     *
     * This method stops execution completely. If the promise is successful with
     * a value, this method will return this value. If the promise was
     * rejected, this method will throw an exception.
     *
     * This effectively turns the asynchronous operation into a synchronous
     * one. In PHP it might be useful to call this on the last promise in a
     * chain.
     *
     * @psalm-return TReturn
     */
    public function wait()
    {
        $hasEvents = true;
        while (self::PENDING === $this->state) {
            if (!$hasEvents) {
                throw new \LogicException('There were no more events in the loop. This promise will never be fulfilled.');
            }

            // As long as the promise is not fulfilled, we tell the event loop
            // to handle events, and to block.
            $hasEvents = Loop\tick(true);
        }

        if (self::FULFILLED === $this->state) {
            // If the state of this promise is fulfilled, we can return the value.
            return $this->value;
        } else {
            // If we got here, it means that the asynchronous operation
            // errored. Therefore we need to throw an exception.
            throw $this->value;
        }
    }

    /**
     * A list of subscribers. Subscribers are the callbacks that want us to let
     * them know if the callback was fulfilled or rejected.
     *
     * @var array
     */
    protected $subscribers = [];

    /**
     * The result of the promise.
     *
     * If the promise was fulfilled, this will be the result value. If the
     * promise was rejected, this property hold the rejection reason.
     */
    protected $value;

    /**
     * This method is used to call either an onFulfilled or onRejected callback.
     *
     * This method makes sure that the result of these callbacks are handled
     * correctly, and any chained promises are also correctly fulfilled or
     * rejected.
     */
    private function invokeCallback(Promise $subPromise, ?callable $callBack = null)
    {
        // We use 'nextTick' to ensure that the event handlers are always
        // triggered outside of the calling stack in which they were originally
        // passed to 'then'.
        //
        // This makes the order of execution more predictable.
        Loop\nextTick(function () use ($callBack, $subPromise) {
            if (is_callable($callBack)) {
                try {
                    $result = $callBack($this->value);
                    if ($result instanceof self) {
                        // If the callback (onRejected or onFulfilled)
                        // returned a promise, we only fulfill or reject the
                        // chained promise once that promise has also been
                        // resolved.
                        $result->then([$subPromise, 'fulfill'], [$subPromise, 'reject']);
                    } else {
                        // If the callback returned any other value, we
                        // immediately fulfill the chained promise.
                        $subPromise->fulfill($result);
                    }
                } catch (\Throwable $e) {
                    // If the event handler threw an exception, we need to make sure that
                    // the chained promise is rejected as well.
                    $subPromise->reject($e);
                }
            } else {
                if (self::FULFILLED === $this->state) {
                    $subPromise->fulfill($this->value);
                } else {
                    $subPromise->reject($this->value);
                }
            }
        });
    }
}
