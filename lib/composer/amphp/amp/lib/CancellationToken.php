<?php

namespace Amp;

/**
 * Cancellation tokens are simple objects that allow registering handlers to subscribe to cancellation requests.
 */
interface CancellationToken
{
    /**
     * Subscribes a new handler to be invoked on a cancellation request.
     *
     * This handler might be invoked immediately in case the token has already been cancelled. Returned generators will
     * automatically be run as coroutines. Any unhandled exceptions will be throw into the event loop.
     *
     * @param callable(CancelledException) $callback Callback to be invoked on a cancellation request. Will receive a
     * `CancelledException` as first argument that may be used to fail the operation's promise.
     *
     * @return string Identifier that can be used to cancel the subscription.
     */
    public function subscribe(callable $callback): string;

    /**
     * Unsubscribes a previously registered handler.
     *
     * The handler will no longer be called as long as this method isn't invoked from a subscribed callback.
     *
     * @param string $id
     *
     * @return void
     */
    public function unsubscribe(string $id);

    /**
     * Returns whether cancellation has been requested yet.
     *
     * @return bool
     */
    public function isRequested(): bool;

    /**
     * Throws the `CancelledException` if cancellation has been requested, otherwise does nothing.
     *
     * @return void
     *
     * @throws CancelledException
     */
    public function throwIfRequested();
}
