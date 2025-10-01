<?php

namespace Doctrine\DBAL\Driver\SQLite3;

use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\ParameterType;
use SQLite3;

use function assert;
use function sprintf;

final class Connection implements ServerInfoAwareConnection
{
    private SQLite3 $connection;

    /** @internal The connection can be only instantiated by its driver. */
    public function __construct(SQLite3 $connection)
    {
        $this->connection = $connection;
    }

    public function prepare(string $sql): Statement
    {
        try {
            $statement = $this->connection->prepare($sql);
        } catch (\Exception $e) {
            throw Exception::new($e);
        }

        assert($statement !== false);

        return new Statement($this->connection, $statement);
    }

    public function query(string $sql): Result
    {
        try {
            $result = $this->connection->query($sql);
        } catch (\Exception $e) {
            throw Exception::new($e);
        }

        assert($result !== false);

        return new Result($result, $this->connection->changes());
    }

    /** @inheritDoc */
    public function quote($value, $type = ParameterType::STRING): string
    {
        return sprintf('\'%s\'', SQLite3::escapeString($value));
    }

    public function exec(string $sql): int
    {
        try {
            $this->connection->exec($sql);
        } catch (\Exception $e) {
            throw Exception::new($e);
        }

        return $this->connection->changes();
    }

    /** @inheritDoc */
    public function lastInsertId($name = null): int
    {
        return $this->connection->lastInsertRowID();
    }

    public function beginTransaction(): bool
    {
        try {
            return $this->connection->exec('BEGIN');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function commit(): bool
    {
        try {
            return $this->connection->exec('COMMIT');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function rollBack(): bool
    {
        try {
            return $this->connection->exec('ROLLBACK');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getNativeConnection(): SQLite3
    {
        return $this->connection;
    }

    public function getServerVersion(): string
    {
        return SQLite3::version()['versionString'];
    }
}
