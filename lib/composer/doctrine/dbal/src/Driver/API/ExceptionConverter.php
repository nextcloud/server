<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\API;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Query;

interface ExceptionConverter
{
    /**
     * Converts a given driver-level exception into a DBAL-level driver exception.
     *
     * Implementors should use the vendor-specific error code and SQLSTATE of the exception
     * and instantiate the most appropriate specialized {@link DriverException} subclass.
     *
     * @param Exception  $exception The driver exception to convert.
     * @param Query|null $query     The SQL query that triggered the exception, if any.
     *
     * @return DriverException An instance of {@link DriverException} or one of its subclasses.
     */
    public function convert(Exception $exception, ?Query $query): DriverException;
}
