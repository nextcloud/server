<?php

declare(strict_types=1);

namespace Sabre\Event\Promise;

use Sabre\Event\Promise;
use Throwable;

/**
 * This file contains a set of functions that are useful for dealing with the
 * Promise object.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */

/**
 * This function takes an array of Promises, and returns a Promise that
 * resolves when all of the given arguments have resolved.
 *
 * The returned Promise will resolve with a value that's an array of all the
 * values the given promises have been resolved with.
 *
 * This array will be in the exact same order as the array of input promises.
 *
 * If any of the given Promises fails, the returned promise will immediately
 * fail with the first Promise that fails, and its reason.
 *
 * @param Promise[] $promises
 */
function all(array $promises): Promise
{
    return new Promise(function ($success, $fail) use ($promises) {
        if (empty($promises)) {
            $success([]);

            return;
        }

        $successCount = 0;
        $completeResult = [];

        foreach ($promises as $promiseIndex => $subPromise) {
            $subPromise->then(
                function ($result) use ($promiseIndex, &$completeResult, &$successCount, $success, $promises) {
                    $completeResult[$promiseIndex] = $result;
                    ++$successCount;
                    if ($successCount === count($promises)) {
                        $success($completeResult);
                    }

                    return $result;
                }
            )->otherwise(
                function ($reason) use ($fail) {
                    $fail($reason);
                }
            );
        }
    });
}

/**
 * The race function returns a promise that resolves or rejects as soon as
 * one of the promises in the argument resolves or rejects.
 *
 * The returned promise will resolve or reject with the value or reason of
 * that first promise.
 *
 * @param Promise[] $promises
 */
function race(array $promises): Promise
{
    return new Promise(function ($success, $fail) use ($promises) {
        $alreadyDone = false;
        foreach ($promises as $promise) {
            $promise->then(
                function ($result) use ($success, &$alreadyDone) {
                    if ($alreadyDone) {
                        return;
                    }
                    $alreadyDone = true;
                    $success($result);
                },
                function ($reason) use ($fail, &$alreadyDone) {
                    if ($alreadyDone) {
                        return;
                    }
                    $alreadyDone = true;
                    $fail($reason);
                }
            );
        }
    });
}

/**
 * Returns a Promise that resolves with the given value.
 *
 * If the value is a promise, the returned promise will attach itself to that
 * promise and eventually get the same state as the followed promise.
 *
 * @param mixed $value
 */
function resolve($value): Promise
{
    if ($value instanceof Promise) {
        return $value->then();
    } else {
        $promise = new Promise();
        $promise->fulfill($value);

        return $promise;
    }
}

/**
 * Returns a Promise that will reject with the given reason.
 */
function reject(Throwable $reason): Promise
{
    $promise = new Promise();
    $promise->reject($reason);

    return $promise;
}
