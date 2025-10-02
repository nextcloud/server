<?php

namespace Doctrine\DBAL\Driver\IBMDB2;

use Doctrine\DBAL\Driver\IBMDB2\Exception\ConnectionError;
use Doctrine\DBAL\Driver\IBMDB2\Exception\PrepareFailed;
use Doctrine\DBAL\Driver\IBMDB2\Exception\StatementError;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use stdClass;

use function assert;
use function db2_autocommit;
use function db2_commit;
use function db2_escape_string;
use function db2_exec;
use function db2_last_insert_id;
use function db2_num_rows;
use function db2_prepare;
use function db2_rollback;
use function db2_server_info;
use function error_get_last;

use const DB2_AUTOCOMMIT_OFF;
use const DB2_AUTOCOMMIT_ON;

final class Connection implements ServerInfoAwareConnection
{
    /** @var resource */
    private $connection;

    /**
     * @internal The connection can be only instantiated by its driver.
     *
     * @param resource $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function getServerVersion()
    {
        $serverInfo = db2_server_info($this->connection);
        assert($serverInfo instanceof stdClass);

        return $serverInfo->DBMS_VER;
    }

    public function prepare(string $sql): DriverStatement
    {
        $stmt = @db2_prepare($this->connection, $sql);

        if ($stmt === false) {
            throw PrepareFailed::new(error_get_last());
        }

        return new Statement($stmt);
    }

    public function query(string $sql): ResultInterface
    {
        return $this->prepare($sql)->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function quote($value, $type = ParameterType::STRING)
    {
        $value = db2_escape_string($value);

        if ($type === ParameterType::INTEGER) {
            return $value;
        }

        return "'" . $value . "'";
    }

    public function exec(string $sql): int
    {
        $stmt = @db2_exec($this->connection, $sql);

        if ($stmt === false) {
            throw StatementError::new();
        }

        return db2_num_rows($stmt);
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        if ($name !== null) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4687',
                'The usage of Connection::lastInsertId() with a sequence name is deprecated.',
            );
        }

        return db2_last_insert_id($this->connection) ?? false;
    }

    public function beginTransaction(): bool
    {
        return db2_autocommit($this->connection, DB2_AUTOCOMMIT_OFF);
    }

    public function commit(): bool
    {
        if (! db2_commit($this->connection)) {
            throw ConnectionError::new($this->connection);
        }

        return db2_autocommit($this->connection, DB2_AUTOCOMMIT_ON);
    }

    public function rollBack(): bool
    {
        if (! db2_rollback($this->connection)) {
            throw ConnectionError::new($this->connection);
        }

        return db2_autocommit($this->connection, DB2_AUTOCOMMIT_ON);
    }

    /** @return resource */
    public function getNativeConnection()
    {
        return $this->connection;
    }
}
