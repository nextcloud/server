<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\API\OCI;

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\OCI8\Exception\Error;
use Doctrine\DBAL\Driver\PDO\PDOException;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DatabaseDoesNotExist;
use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\TransactionRolledBack;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query;

use function explode;
use function str_replace;

/** @internal */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /** @link http://www.dba-oracle.com/t_error_code_list.htm */
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        /** @phpstan-var int|'HY000' $code */ // @phpstan-ignore varTag.type
        $code = $exception->getCode();
        // @phpstan-ignore property.notFound, property.notFound
        if ($code === 'HY000' && isset($exception->errorInfo[1], $exception->errorInfo[2])) {
            $errorInfo            = $exception->errorInfo;
            $exception            = new PDOException($errorInfo[2], $errorInfo[1]);
            $exception->errorInfo = $errorInfo;
            $code                 = $exception->getCode();
        }

        switch ($code) {
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

            case 1918:
                return new DatabaseDoesNotExist($exception, $query);

            case 2091:
                //ORA-02091: transaction rolled back
                //ORA-00001: unique constraint (DOCTRINE.GH3423_UNIQUE) violated
                [, $causeError] = explode("\n", $exception->getMessage(), 2);

                [$causeCode] = explode(': ', $causeError, 2);
                $code        = (int) str_replace('ORA-', '', $causeCode);

                if ($exception instanceof PDOException) {
                    $why = $this->convert(new PDOException($causeError, $code, $exception), $query);
                } else {
                    $why = $this->convert(new Error($causeError, null, $code, $exception), $query);
                }

                return new TransactionRolledBack($why, $query);

            case 2289:
            case 2443:
            case 4080:
                return new DatabaseObjectNotFoundException($exception, $query);

            case 2266:
            case 2291:
            case 2292:
                return new ForeignKeyConstraintViolationException($exception, $query);
        }

        return new DriverException($exception, $query);
    }
}
