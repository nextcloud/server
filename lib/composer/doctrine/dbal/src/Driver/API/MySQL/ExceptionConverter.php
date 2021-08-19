<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\API\MySQL;

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
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
     * @link https://dev.mysql.com/doc/refman/8.0/en/client-error-reference.html
     * @link https://dev.mysql.com/doc/refman/8.0/en/server-error-reference.html
     */
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        switch ($exception->getCode()) {
            case 1213:
                return new DeadlockException($exception, $query);

            case 1205:
                return new LockWaitTimeoutException($exception, $query);

            case 1050:
                return new TableExistsException($exception, $query);

            case 1051:
            case 1146:
                return new TableNotFoundException($exception, $query);

            case 1216:
            case 1217:
            case 1451:
            case 1452:
            case 1701:
                return new ForeignKeyConstraintViolationException($exception, $query);

            case 1062:
            case 1557:
            case 1569:
            case 1586:
                return new UniqueConstraintViolationException($exception, $query);

            case 1054:
            case 1166:
            case 1611:
                return new InvalidFieldNameException($exception, $query);

            case 1052:
            case 1060:
            case 1110:
                return new NonUniqueFieldNameException($exception, $query);

            case 1064:
            case 1149:
            case 1287:
            case 1341:
            case 1342:
            case 1343:
            case 1344:
            case 1382:
            case 1479:
            case 1541:
            case 1554:
            case 1626:
                return new SyntaxErrorException($exception, $query);

            case 1044:
            case 1045:
            case 1046:
            case 1049:
            case 1095:
            case 1142:
            case 1143:
            case 1227:
            case 1370:
            case 1429:
            case 2002:
            case 2005:
                return new ConnectionException($exception, $query);

            case 2006:
                return new ConnectionLost($exception, $query);

            case 1048:
            case 1121:
            case 1138:
            case 1171:
            case 1252:
            case 1263:
            case 1364:
            case 1566:
                return new NotNullConstraintViolationException($exception, $query);
        }

        return new DriverException($exception, $query);
    }
}
