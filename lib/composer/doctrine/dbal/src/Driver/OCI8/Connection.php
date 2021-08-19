<?php

namespace Doctrine\DBAL\Driver\OCI8;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\OCI8\Exception\ConnectionFailed;
use Doctrine\DBAL\Driver\OCI8\Exception\Error;
use Doctrine\DBAL\Driver\OCI8\Exception\SequenceDoesNotExist;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;

use function addcslashes;
use function assert;
use function is_float;
use function is_int;
use function oci_commit;
use function oci_connect;
use function oci_pconnect;
use function oci_rollback;
use function oci_server_version;
use function preg_match;
use function str_replace;

use const OCI_NO_AUTO_COMMIT;

final class Connection implements ServerInfoAwareConnection
{
    /** @var resource */
    protected $dbh;

    /** @var ExecutionMode */
    private $executionMode;

    /**
     * Creates a Connection to an Oracle Database using oci8 extension.
     *
     * @internal The connection can be only instantiated by its driver.
     *
     * @param string $username
     * @param string $password
     * @param string $db
     * @param string $charset
     * @param int    $sessionMode
     * @param bool   $persistent
     *
     * @throws Exception
     */
    public function __construct(
        $username,
        $password,
        $db,
        $charset = '',
        $sessionMode = OCI_NO_AUTO_COMMIT,
        $persistent = false
    ) {
        $dbh = $persistent
            ? @oci_pconnect($username, $password, $db, $charset, $sessionMode)
            : @oci_connect($username, $password, $db, $charset, $sessionMode);

        if ($dbh === false) {
            throw ConnectionFailed::new();
        }

        $this->dbh           = $dbh;
        $this->executionMode = new ExecutionMode();
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        $version = oci_server_version($this->dbh);

        if ($version === false) {
            throw Error::new($this->dbh);
        }

        assert(preg_match('/\s+(\d+\.\d+\.\d+\.\d+\.\d+)\s+/', $version, $matches) === 1);

        return $matches[1];
    }

    public function prepare(string $sql): DriverStatement
    {
        return new Statement($this->dbh, $sql, $this->executionMode);
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
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = str_replace("'", "''", $value);

        return "'" . addcslashes($value, "\000\n\r\\\032") . "'";
    }

    public function exec(string $sql): int
    {
        return $this->prepare($sql)->execute()->rowCount();
    }

    /**
     * {@inheritdoc}
     *
     * @return int|false
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return false;
        }

        $result = $this->query('SELECT ' . $name . '.CURRVAL FROM DUAL')->fetchOne();

        if ($result === false) {
            throw SequenceDoesNotExist::new();
        }

        return (int) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->executionMode->disableAutoCommit();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        if (! oci_commit($this->dbh)) {
            throw Error::new($this->dbh);
        }

        $this->executionMode->enableAutoCommit();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        if (! oci_rollback($this->dbh)) {
            throw Error::new($this->dbh);
        }

        $this->executionMode->enableAutoCommit();

        return true;
    }
}
