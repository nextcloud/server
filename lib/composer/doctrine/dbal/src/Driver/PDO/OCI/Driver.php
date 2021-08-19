<?php

namespace Doctrine\DBAL\Driver\PDO\OCI;

use Doctrine\DBAL\Driver\AbstractOracleDriver;
use Doctrine\DBAL\Driver\PDO\Connection;
use PDO;

final class Driver extends AbstractOracleDriver
{
    /**
     * {@inheritdoc}
     *
     * @return Connection
     */
    public function connect(array $params)
    {
        $driverOptions = $params['driverOptions'] ?? [];

        if (! empty($params['persistent'])) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        return new Connection(
            $this->constructPdoDsn($params),
            $params['user'] ?? '',
            $params['password'] ?? '',
            $driverOptions
        );
    }

    /**
     * Constructs the Oracle PDO DSN.
     *
     * @param mixed[] $params
     *
     * @return string The DSN.
     */
    private function constructPdoDsn(array $params)
    {
        $dsn = 'oci:dbname=' . $this->getEasyConnectString($params);

        if (isset($params['charset'])) {
            $dsn .= ';charset=' . $params['charset'];
        }

        return $dsn;
    }
}
