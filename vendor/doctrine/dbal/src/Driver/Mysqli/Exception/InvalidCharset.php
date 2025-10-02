<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli\Exception;

use Doctrine\DBAL\Driver\AbstractException;
use mysqli;
use mysqli_sql_exception;
use ReflectionProperty;

use function sprintf;

/** @internal */
final class InvalidCharset extends AbstractException
{
    public static function fromCharset(mysqli $connection, string $charset): self
    {
        return new self(
            sprintf('Failed to set charset "%s": %s', $charset, $connection->error),
            $connection->sqlstate,
            $connection->errno,
        );
    }

    public static function upcast(mysqli_sql_exception $exception, string $charset): self
    {
        $p = new ReflectionProperty(mysqli_sql_exception::class, 'sqlstate');
        $p->setAccessible(true);

        return new self(
            sprintf('Failed to set charset "%s": %s', $charset, $exception->getMessage()),
            $p->getValue($exception),
            (int) $exception->getCode(),
            $exception,
        );
    }
}
