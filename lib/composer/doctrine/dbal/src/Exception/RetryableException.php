<?php

namespace Doctrine\DBAL\Exception;

use Throwable;

/**
 * Marker interface for all exceptions where retrying the transaction makes sense.
 *
 * @psalm-immutable
 */
interface RetryableException extends Throwable
{
}
