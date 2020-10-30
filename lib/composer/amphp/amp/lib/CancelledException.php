<?php

namespace Amp;

/**
 * Will be thrown in case an operation is cancelled.
 *
 * @see CancellationToken
 * @see CancellationTokenSource
 */
class CancelledException extends \Exception
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct("The operation was cancelled", 0, $previous);
    }
}
