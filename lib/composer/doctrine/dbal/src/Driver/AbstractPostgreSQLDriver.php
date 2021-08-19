<?php

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\API\PostgreSQL;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\VersionAwarePlatformDriver;

use function preg_match;
use function version_compare;

/**
 * Abstract base implementation of the {@link Driver} interface for PostgreSQL based drivers.
 */
abstract class AbstractPostgreSQLDriver implements VersionAwarePlatformDriver
{
    /**
     * {@inheritdoc}
     */
    public function createDatabasePlatformForVersion($version)
    {
        if (preg_match('/^(?P<major>\d+)(?:\.(?P<minor>\d+)(?:\.(?P<patch>\d+))?)?/', $version, $versionParts) === 0) {
            throw Exception::invalidPlatformVersionSpecified(
                $version,
                '<major_version>.<minor_version>.<patch_version>'
            );
        }

        $majorVersion = $versionParts['major'];
        $minorVersion = $versionParts['minor'] ?? 0;
        $patchVersion = $versionParts['patch'] ?? 0;
        $version      = $majorVersion . '.' . $minorVersion . '.' . $patchVersion;

        if (version_compare($version, '10.0', '>=')) {
            return new PostgreSQL100Platform();
        }

        return new PostgreSQL94Platform();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return new PostgreSQL94Platform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        return new PostgreSQLSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return new PostgreSQL\ExceptionConverter();
    }
}
