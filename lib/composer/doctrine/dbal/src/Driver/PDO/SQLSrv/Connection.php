<?php

namespace Doctrine\DBAL\Driver\PDO\SQLSrv;

use Doctrine\DBAL\Driver\PDO\Connection as PDOConnection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;

final class Connection implements ServerInfoAwareConnection
{
    /** @var PDOConnection */
    private $connection;

    public function __construct(PDOConnection $connection)
    {
        $this->connection = $connection;
    }

    public function prepare(string $sql): StatementInterface
    {
        return new Statement(
            $this->connection->prepare($sql)
        );
    }

    public function query(string $sql): Result
    {
        return $this->connection->query($sql);
    }

    /**
     * {@inheritDoc}
     */
    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->connection->quote($value, $type);
    }

    public function exec(string $sql): int
    {
        return $this->connection->exec($sql);
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return $this->connection->lastInsertId($name);
        }

        return $this->prepare('SELECT CONVERT(VARCHAR(MAX), current_value) FROM sys.sequences WHERE name = ?')
            ->execute([$name])
            ->fetchOne();
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        return $this->connection->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function getServerVersion()
    {
        return $this->connection->getServerVersion();
    }

    public function getWrappedConnection(): PDO
    {
        return $this->connection->getWrappedConnection();
    }
}
