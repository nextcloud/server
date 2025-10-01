<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\API\IBMDB2;

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query;

/**
 * @internal
 *
 * @link https://www.ibm.com/docs/en/db2/11.5?topic=messages-sql
 */
final class ExceptionConverter implements ExceptionConverterInterface
{
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        switch ($exception->getCode()) {
            case -104:
                return new SyntaxErrorException($exception, $query);

            case -203:
                return new NonUniqueFieldNameException($exception, $query);

            case -204:
                return new TableNotFoundException($exception, $query);

            case -206:
                return new InvalidFieldNameException($exception, $query);

            case -407:
                return new NotNullConstraintViolationException($exception, $query);

            case -530:
            case -531:
            case -532:
            case -20356:
                return new ForeignKeyConstraintViolationException($exception, $query);

            case -601:
                return new TableExistsException($exception, $query);

            case -803:
                return new UniqueConstraintViolationException($exception, $query);

            case -1336:
            case -30082:
                return new ConnectionException($exception, $query);
        }

        return new DriverException($exception, $query);
    }
}
