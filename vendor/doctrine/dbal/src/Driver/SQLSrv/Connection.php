<?php

namespace Doctrine\DBAL\Driver\SQLSrv;

use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\SQLSrv\Exception\Error;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;

use function is_float;
use function is_int;
use function sprintf;
use function sqlsrv_begin_transaction;
use function sqlsrv_commit;
use function sqlsrv_query;
use function sqlsrv_rollback;
use function sqlsrv_rows_affected;
use function sqlsrv_server_info;
use function str_replace;

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
        $serverInfo = sqlsrv_server_info($this->connection);

        return $serverInfo['SQLServerVersion'];
    }

    public function prepare(string $sql): DriverStatement
    {
        return new Statement($this->connection, $sql);
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
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return sprintf('%F', $value);
        }

        return "'" . str_replace("'", "''", $value) . "'";
    }

    public function exec(string $sql): int
    {
        $stmt = sqlsrv_query($this->connection, $sql);

        if ($stmt === false) {
            throw Error::new();
        }

        $rowsAffected = sqlsrv_rows_affected($stmt);

        if ($rowsAffected === false) {
            throw Error::new();
        }

        return $rowsAffected;
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

            $result = $this->prepare('SELECT CONVERT(VARCHAR(MAX), current_value) FROM sys.sequences WHERE name = ?')
                ->execute([$name]);
        } else {
            $result = $this->query('SELECT @@IDENTITY');
        }

        return $result->fetchOne();
    }

    public function beginTransaction(): bool
    {
        if (! sqlsrv_begin_transaction($this->connection)) {
            throw Error::new();
        }

        return true;
    }

    public function commit(): bool
    {
        if (! sqlsrv_commit($this->connection)) {
            throw Error::new();
        }

        return true;
    }

    public function rollBack(): bool
    {
        if (! sqlsrv_rollback($this->connection)) {
            throw Error::new();
        }

        return true;
    }

    /** @return resource */
    public function getNativeConnection()
    {
        return $this->connection;
    }
}
