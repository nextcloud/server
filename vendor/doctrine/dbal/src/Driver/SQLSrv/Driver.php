<?php

namespace Doctrine\DBAL\Driver\SQLSrv;

use Doctrine\DBAL\Driver\AbstractSQLServerDriver;
use Doctrine\DBAL\Driver\AbstractSQLServerDriver\Exception\PortWithoutHost;
use Doctrine\DBAL\Driver\SQLSrv\Exception\Error;
use SensitiveParameter;

use function sqlsrv_configure;
use function sqlsrv_connect;

/**
 * Driver for ext/sqlsrv.
 */
final class Driver extends AbstractSQLServerDriver
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
        $serverName = '';

        if (isset($params['host'])) {
            $serverName = $params['host'];

            if (isset($params['port'])) {
                $serverName .= ',' . $params['port'];
            }
        } elseif (isset($params['port'])) {
            throw PortWithoutHost::new();
        }

        $driverOptions = $params['driverOptions'] ?? [];

        if (isset($params['dbname'])) {
            $driverOptions['Database'] = $params['dbname'];
        }

        if (isset($params['charset'])) {
            $driverOptions['CharacterSet'] = $params['charset'];
        }

        if (isset($params['user'])) {
            $driverOptions['UID'] = $params['user'];
        }

        if (isset($params['password'])) {
            $driverOptions['PWD'] = $params['password'];
        }

        if (! isset($driverOptions['ReturnDatesAsStrings'])) {
            $driverOptions['ReturnDatesAsStrings'] = 1;
        }

        if (! sqlsrv_configure('WarningsReturnAsErrors', 0)) {
            throw Error::new();
        }

        $connection = sqlsrv_connect($serverName, $driverOptions);

        if ($connection === false) {
            throw Error::new();
        }

        return new Connection($connection);
    }
}
