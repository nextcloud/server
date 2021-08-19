<?php

namespace Doctrine\DBAL\Driver\PDO;

use Doctrine\DBAL\Driver\Exception as ExceptionInterface;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use PDOStatement;

use function assert;

final class Connection implements ServerInfoAwareConnection
{
    /** @var PDO */
    private $connection;

    /**
     * @internal The connection can be only instantiated by its driver.
     *
     * @param string       $dsn
     * @param string|null  $user
     * @param string|null  $password
     * @param mixed[]|null $options
     *
     * @throws ExceptionInterface
     */
    public function __construct($dsn, $user = null, $password = null, ?array $options = null)
    {
        try {
            $this->connection = new PDO($dsn, (string) $user, (string) $password, (array) $options);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function exec(string $sql): int
    {
        try {
            $result = $this->connection->exec($sql);

            assert($result !== false);

            return $result;
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * {@inheritDoc}
     *
     * @return Statement
     */
    public function prepare(string $sql): StatementInterface
    {
        try {
            $stmt = $this->connection->prepare($sql);
            assert($stmt instanceof PDOStatement);

            return $this->createStatement($stmt);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function query(string $sql): ResultInterface
    {
        try {
            $stmt = $this->connection->query($sql);
            assert($stmt instanceof PDOStatement);

            return new Result($stmt);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->connection->quote($value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        try {
            if ($name === null) {
                return $this->connection->lastInsertId();
            }

            return $this->connection->lastInsertId($name);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Creates a wrapped statement
     */
    protected function createStatement(PDOStatement $stmt): Statement
    {
        return new Statement($stmt);
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

    public function getWrappedConnection(): PDO
    {
        return $this->connection;
    }
}
