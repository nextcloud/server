<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\API\SQLSrv;

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Query;

/**
 * @internal
 */
final class ExceptionConverter implements ExceptionConverterInterface
{
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        return new DriverException($exception, $query);
    }
}
