<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\API\OCI;

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
 */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /**
     * @link http://www.dba-oracle.com/t_error_code_list.htm
     */
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        switch ($exception->getCode()) {
            case 1:
            case 2299:
            case 38911:
                return new UniqueConstraintViolationException($exception, $query);

            case 904:
                return new InvalidFieldNameException($exception, $query);

            case 918:
            case 960:
                return new NonUniqueFieldNameException($exception, $query);

            case 923:
                return new SyntaxErrorException($exception, $query);

            case 942:
                return new TableNotFoundException($exception, $query);

            case 955:
                return new TableExistsException($exception, $query);

            case 1017:
            case 12545:
                return new ConnectionException($exception, $query);

            case 1400:
                return new NotNullConstraintViolationException($exception, $query);

            case 2266:
            case 2291:
            case 2292:
                return new ForeignKeyConstraintViolationException($exception, $query);
        }

        return new DriverException($exception, $query);
    }
}
