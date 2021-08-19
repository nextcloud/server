<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Exception;

use Doctrine\DBAL\Driver\AbstractException;
use mysqli;

use function sprintf;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class InvalidCharset extends AbstractException
{
    public static function fromCharset(mysqli $connection, string $charset): self
    {
        return new self(
            sprintf('Failed to set charset "%s": %s', $charset, $connection->error),
            $connection->sqlstate,
            $connection->errno
        );
    }
}
