<?php

namespace Doctrine\DBAL\Tools\Console\ConnectionProvider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\ConnectionNotFound;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;

use function sprintf;

class SingleConnectionProvider implements ConnectionProvider
{
    private Connection $connection;

    private string $defaultConnectionName;

    public function __construct(Connection $connection, string $defaultConnectionName = 'default')
    {
        $this->connection            = $connection;
        $this->defaultConnectionName = $defaultConnectionName;
    }

    public function getDefaultConnection(): Connection
    {
        return $this->connection;
    }

    public function getConnection(string $name): Connection
    {
        if ($name !== $this->defaultConnectionName) {
            throw new ConnectionNotFound(sprintf('Connection with name "%s" does not exist.', $name));
        }

        return $this->connection;
    }
}
