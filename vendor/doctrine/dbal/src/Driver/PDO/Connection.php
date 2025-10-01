<?php

namespace Doctrine\DBAL\Driver\PDO;

use Doctrine\DBAL\Driver\Exception\UnknownParameterType;
use Doctrine\DBAL\Driver\PDO\PDOException as DriverPDOException;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use PDO;
use PDOException;
use PDOStatement;

use function assert;

final class Connection implements ServerInfoAwareConnection
{
    private PDO $connection;

    /** @internal The connection can be only instantiated by its driver. */
    public function __construct(PDO $connection)
    {
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->connection = $connection;
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
     * {@inheritDoc}
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

            return new Statement($stmt);
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
     * {@inheritDoc}
     *
     * @throws UnknownParameterType
     *
     * @phpstan-assert ParameterType::* $type
     */
    public function quote($value, $type = ParameterType::STRING)
    {
        return $this->connection->quote($value, ParameterTypeMap::convertParamType($type));
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        try {
            if ($name === null) {
                return $this->connection->lastInsertId();
            }

            Deprecation::triggerIfCalledFromOutside(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4687',
                'The usage of Connection::lastInsertId() with a sequence name is deprecated.',
            );

            return $this->connection->lastInsertId($name);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function beginTransaction(): bool
    {
        try {
            return $this->connection->beginTransaction();
        } catch (PDOException $exception) {
            throw DriverPDOException::new($exception);
        }
    }

    public function commit(): bool
    {
        try {
            return $this->connection->commit();
        } catch (PDOException $exception) {
            throw DriverPDOException::new($exception);
        }
    }

    public function rollBack(): bool
    {
        try {
            return $this->connection->rollBack();
        } catch (PDOException $exception) {
            throw DriverPDOException::new($exception);
        }
    }

    public function getNativeConnection(): PDO
    {
        return $this->connection;
    }

    /** @deprecated Call {@see getNativeConnection()} instead. */
    public function getWrappedConnection(): PDO
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5037',
            '%s is deprecated, call getNativeConnection() instead.',
            __METHOD__,
        );

        return $this->getNativeConnection();
    }
}
