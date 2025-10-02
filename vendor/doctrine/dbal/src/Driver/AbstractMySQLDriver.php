<?php

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\API\MySQL;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDb1010Platform;
use Doctrine\DBAL\Platforms\MariaDb1027Platform;
use Doctrine\DBAL\Platforms\MariaDb1043Platform;
use Doctrine\DBAL\Platforms\MariaDb1052Platform;
use Doctrine\DBAL\Platforms\MariaDb1060Platform;
use Doctrine\DBAL\Platforms\MariaDb110700Platform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\VersionAwarePlatformDriver;
use Doctrine\Deprecations\Deprecation;

use function assert;
use function preg_match;
use function stripos;
use function version_compare;

/**
 * Abstract base implementation of the {@see Driver} interface for MySQL based drivers.
 */
abstract class AbstractMySQLDriver implements VersionAwarePlatformDriver
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function createDatabasePlatformForVersion($version)
    {
        $mariadb = stripos($version, 'mariadb') !== false;

        if ($mariadb) {
            $mariaDbVersion = $this->getMariaDbMysqlVersionNumber($version);
            if (version_compare($mariaDbVersion, '11.7.0', '>=')) {
                return new MariaDb110700Platform();
            }

            if (version_compare($mariaDbVersion, '10.10.0', '>=')) {
                return new MariaDb1010Platform();
            }

            if (version_compare($mariaDbVersion, '10.6.0', '>=')) {
                return new MariaDb1060Platform();
            }

            if (version_compare($mariaDbVersion, '10.5.2', '>=')) {
                return new MariaDb1052Platform();
            }

            if (version_compare($mariaDbVersion, '10.4.3', '>=')) {
                return new MariaDb1043Platform();
            }

            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/6110',
                'Support for MariaDB < 10.4 is deprecated and will be removed in DBAL 4.'
                . ' Consider upgrading to a more recent version of MariaDB.',
            );

            if (version_compare($mariaDbVersion, '10.2.7', '>=')) {
                return new MariaDb1027Platform();
            }
        } else {
            $oracleMysqlVersion = $this->getOracleMysqlVersionNumber($version);

            if (version_compare($oracleMysqlVersion, '8.4.0', '>=')) {
                if (! version_compare($version, '8.4.0', '>=')) {
                    Deprecation::trigger(
                        'doctrine/orm',
                        'https://github.com/doctrine/dbal/pull/5779',
                        'Version detection logic for MySQL will change in DBAL 4. '
                            . 'Please specify the version as the server reports it, e.g. "8.4.0" instead of "8.4".',
                    );
                }

                return new MySQL84Platform();
            }

            if (version_compare($oracleMysqlVersion, '8', '>=')) {
                if (! version_compare($version, '8.0.0', '>=')) {
                    Deprecation::trigger(
                        'doctrine/orm',
                        'https://github.com/doctrine/dbal/pull/5779',
                        'Version detection logic for MySQL will change in DBAL 4. '
                            . 'Please specify the version as the server reports it, e.g. "8.0.31" instead of "8".',
                    );
                }

                return new MySQL80Platform();
            }

            if (version_compare($oracleMysqlVersion, '5.7.9', '>=')) {
                if (! version_compare($version, '5.7.9', '>=')) {
                    Deprecation::trigger(
                        'doctrine/orm',
                        'https://github.com/doctrine/dbal/pull/5779',
                        'Version detection logic for MySQL will change in DBAL 4. '
                        . 'Please specify the version as the server reports it, e.g. "5.7.40" instead of "5.7".',
                    );
                }

                return new MySQL57Platform();
            }

            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5072',
                'MySQL 5.6 support is deprecated and will be removed in DBAL 4.'
                . ' Consider upgrading to MySQL 5.7 or later.',
            );
        }

        return $this->getDatabasePlatform();
    }

    /**
     * Get a normalized 'version number' from the server string
     * returned by Oracle MySQL servers.
     *
     * @param string $versionString Version string returned by the driver, i.e. '5.7.10'
     *
     * @throws Exception
     */
    private function getOracleMysqlVersionNumber(string $versionString): string
    {
        if (
            preg_match(
                '/^(?P<major>\d+)(?:\.(?P<minor>\d+)(?:\.(?P<patch>\d+))?)?/',
                $versionString,
                $versionParts,
            ) !== 1
        ) {
            throw Exception::invalidPlatformVersionSpecified(
                $versionString,
                '<major_version>.<minor_version>.<patch_version>',
            );
        }

        $majorVersion = $versionParts['major'];
        $minorVersion = $versionParts['minor'] ?? 0;
        $patchVersion = $versionParts['patch'] ?? null;

        if ($majorVersion === '5' && $minorVersion === '7') {
            $patchVersion ??= '9';
        } else {
            $patchVersion ??= '0';
        }

        return $majorVersion . '.' . $minorVersion . '.' . $patchVersion;
    }

    /**
     * Detect MariaDB server version, including hack for some mariadb distributions
     * that starts with the prefix '5.5.5-'
     *
     * @param string $versionString Version string as returned by mariadb server, i.e. '5.5.5-Mariadb-10.0.8-xenial'
     *
     * @throws Exception
     */
    private function getMariaDbMysqlVersionNumber(string $versionString): string
    {
        if (stripos($versionString, 'MariaDB') === 0) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/dbal/pull/5779',
                'Version detection logic for MySQL will change in DBAL 4. '
                    . 'Please specify the version as the server reports it, '
                    . 'e.g. "10.9.3-MariaDB" instead of "mariadb-10.9".',
            );
        }

        if (
            preg_match(
                '/^(?:5\.5\.5-)?(mariadb-)?(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)/i',
                $versionString,
                $versionParts,
            ) !== 1
        ) {
            throw Exception::invalidPlatformVersionSpecified(
                $versionString,
                '^(?:5\.5\.5-)?(mariadb-)?<major_version>.<minor_version>.<patch_version>',
            );
        }

        return $versionParts['major'] . '.' . $versionParts['minor'] . '.' . $versionParts['patch'];
    }

    /**
     * {@inheritDoc}
     *
     * @return AbstractMySQLPlatform
     */
    public function getDatabasePlatform()
    {
        return new MySQLPlatform();
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@link AbstractMySQLPlatform::createSchemaManager()} instead.
     *
     * @return MySQLSchemaManager
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5458',
            'AbstractMySQLDriver::getSchemaManager() is deprecated.'
                . ' Use MySQLPlatform::createSchemaManager() instead.',
        );

        assert($platform instanceof AbstractMySQLPlatform);

        return new MySQLSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return new MySQL\ExceptionConverter();
    }
}
