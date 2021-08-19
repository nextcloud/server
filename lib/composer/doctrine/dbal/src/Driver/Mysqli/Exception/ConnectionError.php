<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Exception;

use Doctrine\DBAL\Driver\AbstractException;
use mysqli;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class ConnectionError extends AbstractException
{
    public static function new(mysqli $connection): self
    {
        return new self($connection->error, $connection->sqlstate, $connection->errno);
    }
}
