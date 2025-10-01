<?php

namespace Doctrine\DBAL\Driver\PDO\SQLSrv;

use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\PDO\Connection as PDOConnection;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\Deprecations\Deprecation;
use PDO;

final class Connection extends AbstractConnectionMiddleware
{
    private PDOConnection $connection;

    public function __construct(PDOConnection $connection)
    {
        parent::__construct($connection);

        $this->connection = $connection;
    }

    public function prepare(string $sql): StatementInterface
    {
        return new Statement(
            $this->connection->prepare($sql),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return parent::lastInsertId($name);
        }

        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4687',
            'The usage of Connection::lastInsertId() with a sequence name is deprecated.',
        );

        $statement = $this->prepare(
            'SELECT CONVERT(VARCHAR(MAX), current_value) FROM sys.sequences WHERE name = ?',
        );
        $statement->bindValue(1, $name);

        return $statement->execute()
            ->fetchOne();
    }

    public function getNativeConnection(): PDO
    {
        return $this->connection->getNativeConnection();
    }

    /** @deprecated Call {@see getNativeConnection()} instead. */
    public function getWrappedConnection(): PDO
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5037',
            '%s is deprecated, call getNativeConnection() instead.',
            __METHOD__,
        );

        return $this->connection->getWrappedConnection();
    }
}
