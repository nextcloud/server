<?php

namespace Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\Mysqli\Exception\ConnectionError;
use Doctrine\DBAL\Driver\Mysqli\Exception\ConnectionFailed;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;
use mysqli;

use function floor;
use function mysqli_init;
use function stripos;

final class Connection implements ServerInfoAwareConnection
{
    /**
     * Name of the option to set connection flags
     */
    public const OPTION_FLAGS = 'flags';

    /** @var mysqli */
    private $conn;

    /**
     * @internal The connection can be only instantiated by its driver.
     *
     * @param iterable<Initializer> $preInitializers
     * @param iterable<Initializer> $postInitializers
     *
     * @throws Exception
     */
    public function __construct(
        ?string $host = null,
        ?string $username = null,
        ?string $password = null,
        ?string $database = null,
        ?int $port = null,
        ?string $socket = null,
        ?int $flags = null,
        iterable $preInitializers = [],
        iterable $postInitializers = []
    ) {
        $connection = mysqli_init();

        foreach ($preInitializers as $initializer) {
            $initializer->initialize($connection);
        }

        if (! @$connection->real_connect($host, $username, $password, $database, $port, $socket, $flags)) {
            throw ConnectionFailed::new($connection);
        }

        foreach ($postInitializers as $initializer) {
            $initializer->initialize($connection);
        }

        $this->conn = $connection;
    }

    /**
     * Retrieves mysqli native resource handle.
     *
     * Could be used if part of your application is not using DBAL.
     *
     * @return mysqli
     */
    public function getWrappedResourceHandle()
    {
        return $this->conn;
    }

    /**
     * {@inheritdoc}
     *
     * The server version detection includes a special case for MariaDB
     * to support '5.5.5-' prefixed versions introduced in Maria 10+
     *
     * @link https://jira.mariadb.org/browse/MDEV-4088
     */
    public function getServerVersion()
    {
        $serverInfos = $this->conn->get_server_info();
        if (stripos($serverInfos, 'mariadb') !== false) {
            return $serverInfos;
        }

        $majorVersion = floor($this->conn->server_version / 10000);
        $minorVersion = floor(($this->conn->server_version - $majorVersion * 10000) / 100);
        $patchVersion = floor($this->conn->server_version - $majorVersion * 10000 - $minorVersion * 100);

        return $majorVersion . '.' . $minorVersion . '.' . $patchVersion;
    }

    public function prepare(string $sql): DriverStatement
    {
        return new Statement($this->conn, $sql);
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
        return "'" . $this->conn->escape_string($value) . "'";
    }

    public function exec(string $sql): int
    {
        if ($this->conn->query($sql) === false) {
            throw ConnectionError::new($this->conn);
        }

        return $this->conn->affected_rows;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        return $this->conn->insert_id;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->conn->query('START TRANSACTION');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->conn->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        return $this->conn->rollback();
    }
}
