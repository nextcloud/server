<?php

namespace Doctrine\DBAL\Driver\OCI8;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\OCI8\Exception\Error;
use Doctrine\DBAL\Driver\OCI8\Exception\SequenceDoesNotExist;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\SQL\Parser;
use Doctrine\Deprecations\Deprecation;

use function addcslashes;
use function assert;
use function is_float;
use function is_int;
use function is_resource;
use function oci_commit;
use function oci_parse;
use function oci_rollback;
use function oci_server_version;
use function preg_match;
use function str_replace;

final class Connection implements ServerInfoAwareConnection
{
    /** @var resource */
    private $connection;

    private Parser $parser;
    private ExecutionMode $executionMode;

    /**
     * @internal The connection can be only instantiated by its driver.
     *
     * @param resource $connection
     */
    public function __construct($connection)
    {
        $this->connection    = $connection;
        $this->parser        = new Parser(false);
        $this->executionMode = new ExecutionMode();
    }

    public function getServerVersion(): string
    {
        $version = oci_server_version($this->connection);

        if ($version === false) {
            throw Error::new($this->connection);
        }

        $result = preg_match('/\s+(\d+\.\d+\.\d+\.\d+\.\d+)\s+/', $version, $matches);
        assert($result === 1);

        return $matches[1];
    }

    /** @throws Parser\Exception */
    public function prepare(string $sql): DriverStatement
    {
        $visitor = new ConvertPositionalToNamedPlaceholders();

        $this->parser->parse($sql, $visitor);

        $statement = oci_parse($this->connection, $visitor->getSQL());
        assert(is_resource($statement));

        return new Statement($this->connection, $statement, $visitor->getParameterMap(), $this->executionMode);
    }

    /**
     * @throws Exception
     * @throws Parser\Exception
     */
    public function query(string $sql): ResultInterface
    {
        return $this->prepare($sql)->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function quote($value, $type = ParameterType::STRING)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = str_replace("'", "''", $value);

        return "'" . addcslashes($value, "\000\n\r\\\032") . "'";
    }

    /**
     * @throws Exception
     * @throws Parser\Exception
     */
    public function exec(string $sql): int
    {
        return $this->prepare($sql)->execute()->rowCount();
    }

    /**
     * {@inheritDoc}
     *
     * @param string|null $name
     *
     * @return int|false
     *
     * @throws Parser\Exception
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return false;
        }

        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4687',
            'The usage of Connection::lastInsertId() with a sequence name is deprecated.',
        );

        $result = $this->query('SELECT ' . $name . '.CURRVAL FROM DUAL')->fetchOne();

        if ($result === false) {
            throw SequenceDoesNotExist::new();
        }

        return (int) $result;
    }

    public function beginTransaction(): bool
    {
        $this->executionMode->disableAutoCommit();

        return true;
    }

    public function commit(): bool
    {
        if (! @oci_commit($this->connection)) {
            throw Error::new($this->connection);
        }

        $this->executionMode->enableAutoCommit();

        return true;
    }

    public function rollBack(): bool
    {
        if (! oci_rollback($this->connection)) {
            throw Error::new($this->connection);
        }

        $this->executionMode->enableAutoCommit();

        return true;
    }

    /** @return resource */
    public function getNativeConnection()
    {
        return $this->connection;
    }
}
