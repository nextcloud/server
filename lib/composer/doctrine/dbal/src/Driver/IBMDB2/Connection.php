<?php

namespace Doctrine\DBAL\Driver\IBMDB2;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\IBMDB2\Exception\ConnectionError;
use Doctrine\DBAL\Driver\IBMDB2\Exception\ConnectionFailed;
use Doctrine\DBAL\Driver\IBMDB2\Exception\PrepareFailed;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;
use stdClass;

use function assert;
use function db2_autocommit;
use function db2_commit;
use function db2_connect;
use function db2_escape_string;
use function db2_exec;
use function db2_last_insert_id;
use function db2_num_rows;
use function db2_pconnect;
use function db2_prepare;
use function db2_rollback;
use function db2_server_info;
use function error_get_last;
use function is_bool;

use const DB2_AUTOCOMMIT_OFF;
use const DB2_AUTOCOMMIT_ON;

final class Connection implements ServerInfoAwareConnection
{
    /** @var resource */
    private $conn;

    /**
     * @internal The connection can be only instantiated by its driver.
     *
     * @param array<string,mixed> $driverOptions
     *
     * @throws Exception
     */
    public function __construct(
        string $database,
        bool $persistent,
        string $username,
        string $password,
        array $driverOptions = []
    ) {
        if ($persistent) {
            $conn = db2_pconnect($database, $username, $password, $driverOptions);
        } else {
            $conn = db2_connect($database, $username, $password, $driverOptions);
        }

        if ($conn === false) {
            throw ConnectionFailed::new();
        }

        $this->conn = $conn;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        $serverInfo = db2_server_info($this->conn);
        assert($serverInfo instanceof stdClass);

        return $serverInfo->DBMS_VER;
    }

    public function prepare(string $sql): DriverStatement
    {
        $stmt = @db2_prepare($this->conn, $sql);

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
     * {@inheritdoc}
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
        $stmt = @db2_exec($this->conn, $sql);

        if ($stmt === false) {
            throw ConnectionError::new($this->conn);
        }

        return db2_num_rows($stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        return db2_last_insert_id($this->conn);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $result = db2_autocommit($this->conn, DB2_AUTOCOMMIT_OFF);
        assert(is_bool($result));

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        if (! db2_commit($this->conn)) {
            throw ConnectionError::new($this->conn);
        }

        $result = db2_autocommit($this->conn, DB2_AUTOCOMMIT_ON);
        assert(is_bool($result));

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        if (! db2_rollback($this->conn)) {
            throw ConnectionError::new($this->conn);
        }

        $result = db2_autocommit($this->conn, DB2_AUTOCOMMIT_ON);
        assert(is_bool($result));

        return $result;
    }
}
