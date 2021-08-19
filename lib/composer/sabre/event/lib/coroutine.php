<?php

declare(strict_types=1);

namespace Sabre\Event;

use Generator;
use Throwable;

/**
 * Turn asynchronous promise-based code into something that looks synchronous
 * again, through the use of generators.
 *
 * Example without coroutines:
 *
 * $promise = $httpClient->request('GET', '/foo');
 * $promise->then(function($value) {
 *
 *   return $httpClient->request('DELETE','/foo');
 *
 * })->then(function($value) {
 *
 *   return $httpClient->request('PUT', '/foo');
 *
 * })->error(function($reason) {
 *
 *   echo "Failed because: $reason\n";
 *
 * });
 *
 * Example with coroutines:
 *
 * coroutine(function() {
 *
 *   try {
 *     yield $httpClient->request('GET', '/foo');
 *     yield $httpClient->request('DELETE', /foo');
 *     yield $httpClient->request('PUT', '/foo');
 *   } catch(\Throwable $reason) {
 *     echo "Failed because: $reason\n";
 *   }
 *
 * });
 *
 * @return \Sabre\Event\Promise
 *
 * @psalm-template TReturn
 * @psalm-param callable():\Generator<mixed, mixed, mixed, TReturn> $gen
 * @psalm-return Promise<TReturn>
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
function coroutine(callable $gen): Promise
{
    $generator = $gen();
    if (!$generator instanceof Generator) {
        throw new \InvalidArgumentException('You must pass a generator function');
    }

    // This is the value we're returning.
    $promise = new Promise();

    /**
     * So tempted to use the mythical y-combinator here, but it's not needed in
     * PHP.
     */
    $advanceGenerator = function () use (&$advanceGenerator, $generator, $promise) {
        while ($generator->valid()) {
            $yieldedValue = $generator->current();
            if ($yieldedValue instanceof Promise) {
                $yieldedValue->then(
                    function ($value) use ($generator, &$advanceGenerator) {
                        $generator->send($value);
                        $advanceGenerator();
                    },
                    function (Throwable $reason) use ($generator, $advanceGenerator) {
                        $generator->throw($reason);
                        $advanceGenerator();
                    }
                )->otherwise(function (Throwable $reason) use ($promise) {
                    // This error handler would be called, if something in the
                    // generator throws an exception, and it's not caught
                    // locally.
                    $promise->reject($reason);
                });
                // We need to break out of the loop, because $advanceGenerator
                // will be called asynchronously when the promise has a result.
                break;
            } else {
                // If the value was not a promise, we'll just let it pass through.
                $generator->send($yieldedValue);
            }
        }

        // If the generator is at the end, and we didn't run into an exception,
        // We're grabbing the "return" value and fulfilling our top-level
        // promise with its value.
        if (!$generator->valid() && Promise::PENDING === $promise->state) {
            $returnValue = $generator->getReturn();

            // The return value is a promise.
            if ($returnValue instanceof Promise) {
                $returnValue->then(function ($value) use ($promise) {
                    $promise->fulfill($value);
                }, function (Throwable $reason) use ($promise) {
                    $promise->reject($reason);
                });
            } else {
                $promise->fulfill($returnValue);
            }
        }
    };

    try {
        $advanceGenerator();
    } catch (Throwable $e) {
        $promise->reject($e);
    }

    return $promise;
}
