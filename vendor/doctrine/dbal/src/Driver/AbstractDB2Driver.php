<?php

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\IBMDB2\ExceptionConverter;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2111Platform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Schema\DB2SchemaManager;
use Doctrine\DBAL\VersionAwarePlatformDriver;
use Doctrine\Deprecations\Deprecation;

use function assert;
use function preg_match;
use function version_compare;

/**
 * Abstract base implementation of the {@see Driver} interface for IBM DB2 based drivers.
 */
abstract class AbstractDB2Driver implements VersionAwarePlatformDriver
{
    /**
     * {@inheritDoc}
     */
    public function getDatabasePlatform()
    {
        return new DB2Platform();
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@link DB2Platform::createSchemaManager()} instead.
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5458',
            'AbstractDB2Driver::getSchemaManager() is deprecated.'
                . ' Use DB2Platform::createSchemaManager() instead.',
        );

        assert($platform instanceof DB2Platform);

        return new DB2SchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }

    /**
     * {@inheritDoc}
     */
    public function createDatabasePlatformForVersion($version)
    {
        if (version_compare($this->getVersionNumber($version), '11.1', '>=')) {
            return new DB2111Platform();
        }

        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5156',
            'IBM DB2 < 11.1 support is deprecated and will be removed in DBAL 4.'
                . ' Consider upgrading to IBM DB2 11.1 or later.',
        );

        return $this->getDatabasePlatform();
    }

    /**
     * Detects IBM DB2 server version
     *
     * @param string $versionString Version string as returned by IBM DB2 server, i.e. 'DB2/LINUXX8664 11.5.8.0'
     *
     * @throws DBALException
     */
    private function getVersionNumber(string $versionString): string
    {
        if (
            preg_match(
                '/^(?:[^\s]+\s)?(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)/i',
                $versionString,
                $versionParts,
            ) !== 1
        ) {
            throw DBALException::invalidPlatformVersionSpecified(
                $versionString,
                '^(?:[^\s]+\s)?<major_version>.<minor_version>.<patch_version>',
            );
        }

        return $versionParts['major'] . '.' . $versionParts['minor'] . '.' . $versionParts['patch'];
    }
}
