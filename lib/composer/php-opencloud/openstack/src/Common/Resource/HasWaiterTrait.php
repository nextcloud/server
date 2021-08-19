<?php

declare(strict_types=1);

namespace OpenStack\Common\Resource;

use OpenStack\Common\Error\BadResponseError;

/**
 * Contains reusable functionality for resources that have long operations which require waiting in
 * order to reach a particular state.
 *
 * @codeCoverageIgnore
 */
trait HasWaiterTrait
{
    /**
     * Provides a blocking operation until the resource has reached a particular state. The method
     * will enter a loop, requesting feedback from the remote API until it sends back an appropriate
     * status.
     *
     * @param string $status      The state to be reached
     * @param int    $timeout     The maximum timeout. If the total time taken by the waiter has reached
     *                            or exceed this timeout, the blocking operation will immediately cease.
     * @param int    $sleepPeriod the amount of time to pause between each HTTP request
     */
    public function waitUntil(string $status, $timeout = 60, int $sleepPeriod = 1)
    {
        $startTime = time();

        while (true) {
            $this->retrieve();

            if ($this->status == $status || $this->shouldHalt($timeout, $startTime)) {
                break;
            }

            sleep($sleepPeriod);
        }
    }

    /**
     * Provides a blocking operation until the resource has reached a particular state. The method
     * will enter a loop, executing the callback until TRUE is returned. This provides great
     * flexibility.
     *
     * @param callable $fn          An anonymous function that will be executed on every iteration. You can
     *                              encapsulate your own logic to determine whether the resource has
     *                              successfully transitioned. When TRUE is returned by the callback,
     *                              the loop will end.
     * @param int|bool $timeout     The maximum timeout in seconds. If the total time taken by the waiter has reached
     *                              or exceed this timeout, the blocking operation will immediately cease. If FALSE
     *                              is provided, the timeout will never be considered.
     * @param int      $sleepPeriod the amount of time to pause between each HTTP request
     */
    public function waitWithCallback(callable $fn, $timeout = 60, int $sleepPeriod = 1)
    {
        $startTime = time();

        while (true) {
            $this->retrieve();

            $response = call_user_func_array($fn, [$this]);

            if (true === $response || $this->shouldHalt($timeout, $startTime)) {
                break;
            }

            sleep($sleepPeriod);
        }
    }

    /**
     * Internal method used to identify whether a timeout has been exceeded.
     *
     * @param bool|int $timeout
     *
     * @return bool
     */
    private function shouldHalt($timeout, int $startTime)
    {
        if (false === $timeout) {
            return false;
        }

        return time() - $startTime >= $timeout;
    }

    /**
     * Convenience method providing a blocking operation until the resource transitions to an
     * ``ACTIVE`` status.
     *
     * @param int|bool $timeout The maximum timeout in seconds. If the total time taken by the waiter has reached
     *                          or exceed this timeout, the blocking operation will immediately cease. If FALSE
     *                          is provided, the timeout will never be considered.
     */
    public function waitUntilActive($timeout = false)
    {
        $this->waitUntil('ACTIVE', $timeout);
    }

    public function waitUntilDeleted($timeout = 60, int $sleepPeriod = 1)
    {
        $startTime = time();

        while (true) {
            try {
                $this->retrieve();
            } catch (BadResponseError $e) {
                if (404 === $e->getResponse()->getStatusCode()) {
                    break;
                }
                throw $e;
            }

            if ($this->shouldHalt($timeout, $startTime)) {
                break;
            }

            sleep($sleepPeriod);
        }
    }
}
