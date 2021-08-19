<?php

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\SQLSrv\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform;
use Doctrine\DBAL\Schema\SQLServerSchemaManager;

/**
 * Abstract base implementation of the {@link Driver} interface for Microsoft SQL Server based drivers.
 */
abstract class AbstractSQLServerDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new SQLServer2012Platform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        return new SQLServerSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }
}
