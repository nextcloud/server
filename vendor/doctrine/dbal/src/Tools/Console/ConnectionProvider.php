<?php

namespace Doctrine\DBAL\Tools\Console;

use Doctrine\DBAL\Connection;

interface ConnectionProvider
{
    public function getDefaultConnection(): Connection;

    /** @throws ConnectionNotFound in case a connection with the given name does not exist. */
    public function getConnection(string $name): Connection;
}
