<?php

namespace Doctrine\DBAL\Driver\IBMDB2;

use Doctrine\DBAL\Driver\AbstractDB2Driver;

final class Driver extends AbstractDB2Driver
{
    /**
     * {@inheritdoc}
     *
     * @return Connection
     */
    public function connect(array $params)
    {
        return new Connection(
            DataSourceName::fromConnectionParameters($params)->toString(),
            isset($params['persistent']) && $params['persistent'] === true,
            $params['user'] ?? '',
            $params['password'] ?? '',
            $params['driverOptions'] ?? []
        );
    }
}
