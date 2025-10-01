<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Exception;

use Doctrine\DBAL\Driver\AbstractException;
use mysqli;
use mysqli_sql_exception;
use ReflectionProperty;

use function assert;

/** @internal */
final class ConnectionFailed extends AbstractException
{
    public static function new(mysqli $connection): self
    {
        $error = $connection->connect_error;
        assert($error !== null);

        return new self($error, 'HY000', $connection->connect_errno);
    }

    public static function upcast(mysqli_sql_exception $exception): self
    {
        $p = new ReflectionProperty(mysqli_sql_exception::class, 'sqlstate');
        $p->setAccessible(true);

        return new self($exception->getMessage(), $p->getValue($exception), (int) $exception->getCode(), $exception);
    }
}
