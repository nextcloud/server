<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Exception;

use Doctrine\DBAL\Driver\AbstractException;
use mysqli_stmt;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class StatementError extends AbstractException
{
    public static function new(mysqli_stmt $statement): self
    {
        return new self($statement->error, $statement->sqlstate, $statement->errno);
    }
}
