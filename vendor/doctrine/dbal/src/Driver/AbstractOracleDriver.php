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
use Doctrine\Deprecations\Deprecation;

use function assert;

/**
 * Abstract base implementation of the {@see Driver} interface for Oracle based drivers.
 */
abstract class AbstractOracleDriver implements Driver
{
    /**
     * {@inheritDoc}
     */
    public function getDatabasePlatform()
    {
        return new OraclePlatform();
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@link OraclePlatform::createSchemaManager()} instead.
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5458',
            'AbstractOracleDriver::getSchemaManager() is deprecated.'
                . ' Use OraclePlatform::createSchemaManager() instead.',
        );

        assert($platform instanceof OraclePlatform);

        return new OracleSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return new OCI\ExceptionConverter();
    }

    /**
     * Returns an appropriate Easy Connect String for the given parameters.
     *
     * @param array<string, mixed> $params The connection parameters to return the Easy Connect String for.
     *
     * @return string
     */
    protected function getEasyConnectString(array $params)
    {
        return (string) EasyConnectString::fromConnectionParameters($params);
    }
}
