<?php

namespace Doctrine\DBAL\Driver\SQLite3;

use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\DBAL\Driver\API\SQLite\UserDefinedFunctions;
use SensitiveParameter;
use SQLite3;

final class Driver extends AbstractSQLiteDriver
{
    /**
     * {@inheritDoc}
     */
    public function connect(
        #[SensitiveParameter]
        array $params
    ): Connection {
        $isMemory = (bool) ($params['memory'] ?? false);

        if (isset($params['path'])) {
            if ($isMemory) {
                throw new Exception(
                    'Invalid connection settings: specifying both parameters "path" and "memory" is ambiguous.',
                );
            }

            $filename = $params['path'];
        } elseif ($isMemory) {
            $filename = ':memory:';
        } else {
            throw new Exception(
                'Invalid connection settings: specify either the "path" or the "memory" parameter for SQLite3.',
            );
        }

        try {
            $connection = new SQLite3($filename);
        } catch (\Exception $e) {
            throw Exception::new($e);
        }

        $connection->enableExceptions(true);

        UserDefinedFunctions::register([$connection, 'createFunction']);

        return new Connection($connection);
    }
}
