<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\API\SQLite;

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\ReadOnlyException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query;

use function strpos;

/** @internal */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /** @link http://www.sqlite.org/c3ref/c_abort.html */
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        if (strpos($exception->getMessage(), 'database is locked') !== false) {
            return new LockWaitTimeoutException($exception, $query);
        }

        if (
            strpos($exception->getMessage(), 'must be unique') !== false ||
            strpos($exception->getMessage(), 'is not unique') !== false ||
            strpos($exception->getMessage(), 'are not unique') !== false ||
            strpos($exception->getMessage(), 'UNIQUE constraint failed') !== false
        ) {
            return new UniqueConstraintViolationException($exception, $query);
        }

        if (
            strpos($exception->getMessage(), 'may not be NULL') !== false ||
            strpos($exception->getMessage(), 'NOT NULL constraint failed') !== false
        ) {
            return new NotNullConstraintViolationException($exception, $query);
        }

        if (strpos($exception->getMessage(), 'no such table:') !== false) {
            return new TableNotFoundException($exception, $query);
        }

        if (strpos($exception->getMessage(), 'already exists') !== false) {
            return new TableExistsException($exception, $query);
        }

        if (strpos($exception->getMessage(), 'has no column named') !== false) {
            return new InvalidFieldNameException($exception, $query);
        }

        if (strpos($exception->getMessage(), 'ambiguous column name') !== false) {
            return new NonUniqueFieldNameException($exception, $query);
        }

        if (strpos($exception->getMessage(), 'syntax error') !== false) {
            return new SyntaxErrorException($exception, $query);
        }

        if (strpos($exception->getMessage(), 'attempt to write a readonly database') !== false) {
            return new ReadOnlyException($exception, $query);
        }

        if (strpos($exception->getMessage(), 'unable to open database file') !== false) {
            return new ConnectionException($exception, $query);
        }

        if (strpos($exception->getMessage(), 'FOREIGN KEY constraint failed') !== false) {
            return new ForeignKeyConstraintViolationException($exception, $query);
        }

        return new DriverException($exception, $query);
    }
}
