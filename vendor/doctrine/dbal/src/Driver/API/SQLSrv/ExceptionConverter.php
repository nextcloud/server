<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\API\SQLSrv;

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
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
 * @link https://docs.microsoft.com/en-us/sql/relational-databases/errors-events/database-engine-events-and-errors
 */
final class ExceptionConverter implements ExceptionConverterInterface
{
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        switch ($exception->getCode()) {
            case 102:
                return new SyntaxErrorException($exception, $query);

            case 207:
                return new InvalidFieldNameException($exception, $query);

            case 208:
                return new TableNotFoundException($exception, $query);

            case 209:
                return new NonUniqueFieldNameException($exception, $query);

            case 515:
                return new NotNullConstraintViolationException($exception, $query);

            case 547:
            case 4712:
                return new ForeignKeyConstraintViolationException($exception, $query);

            case 2601:
            case 2627:
                return new UniqueConstraintViolationException($exception, $query);

            case 2714:
                return new TableExistsException($exception, $query);

            case 3701:
            case 15151:
                return new DatabaseObjectNotFoundException($exception, $query);

            case 11001:
            case 18456:
                return new ConnectionException($exception, $query);
        }

        return new DriverException($exception, $query);
    }
}
