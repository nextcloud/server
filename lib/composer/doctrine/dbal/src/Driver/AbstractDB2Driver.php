<?php

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\IBMDB2\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Schema\DB2SchemaManager;

/**
 * Abstract base implementation of the {@link Driver} interface for IBM DB2 based drivers.
 */
abstract class AbstractDB2Driver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new DB2Platform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        return new DB2SchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }
}
