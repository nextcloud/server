<?php

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\AbstractOracleDriver\EasyConnectString;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\API\OCI;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\OracleSchemaManager;

/**
 * Abstract base implementation of the {@link Driver} interface for Oracle based drivers.
 */
abstract class AbstractOracleDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new OraclePlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        return new OracleSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return new OCI\ExceptionConverter();
    }

    /**
     * Returns an appropriate Easy Connect String for the given parameters.
     *
     * @param mixed[] $params The connection parameters to return the Easy Connect String for.
     *
     * @return string
     */
    protected function getEasyConnectString(array $params)
    {
        return (string) EasyConnectString::fromConnectionParameters($params);
    }
}
