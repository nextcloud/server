<?php

namespace Doctrine\DBAL\Driver\OCI8;

use Doctrine\DBAL\Driver\AbstractOracleDriver;
use Doctrine\DBAL\Driver\OCI8\Exception\ConnectionFailed;
use Doctrine\DBAL\Driver\OCI8\Exception\InvalidConfiguration;
use SensitiveParameter;

use function oci_connect;
use function oci_new_connect;
use function oci_pconnect;

use const OCI_NO_AUTO_COMMIT;

/**
 * A Doctrine DBAL driver for the Oracle OCI8 PHP extensions.
 */
final class Driver extends AbstractOracleDriver
{
    /**
     * {@inheritDoc}
     *
     * @return Connection
     */
    public function connect(
        #[SensitiveParameter]
        array $params
    ) {
        $username    = $params['user'] ?? '';
        $password    = $params['password'] ?? '';
        $charset     = $params['charset'] ?? '';
        $sessionMode = $params['sessionMode'] ?? OCI_NO_AUTO_COMMIT;

        $connectionString = $this->getEasyConnectString($params);

        $persistent = ! empty($params['persistent']);
        $exclusive  = ! empty($params['driverOptions']['exclusive']);

        if ($persistent && $exclusive) {
            throw InvalidConfiguration::forPersistentAndExclusive();
        }

        if ($persistent) {
            $connection = @oci_pconnect($username, $password, $connectionString, $charset, $sessionMode);
        } elseif ($exclusive) {
            $connection = @oci_new_connect($username, $password, $connectionString, $charset, $sessionMode);
        } else {
            $connection = @oci_connect($username, $password, $connectionString, $charset, $sessionMode);
        }

        if ($connection === false) {
            throw ConnectionFailed::new();
        }

        return new Connection($connection);
    }
}
