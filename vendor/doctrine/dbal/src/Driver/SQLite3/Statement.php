<?php

namespace Doctrine\DBAL\Driver\SQLite3;

use Doctrine\DBAL\Driver\Exception\UnknownParameterType;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use SQLite3;
use SQLite3Stmt;

use function assert;
use function func_num_args;
use function is_int;

use const SQLITE3_BLOB;
use const SQLITE3_INTEGER;
use const SQLITE3_NULL;
use const SQLITE3_TEXT;

final class Statement implements StatementInterface
{
    private const PARAM_TYPE_MAP = [
        ParameterType::NULL => SQLITE3_NULL,
        ParameterType::INTEGER => SQLITE3_INTEGER,
        ParameterType::STRING => SQLITE3_TEXT,
        ParameterType::ASCII => SQLITE3_TEXT,
        ParameterType::BINARY => SQLITE3_BLOB,
        ParameterType::LARGE_OBJECT => SQLITE3_BLOB,
        ParameterType::BOOLEAN => SQLITE3_INTEGER,
    ];

    private SQLite3 $connection;
    private SQLite3Stmt $statement;

    /** @internal The statement can be only instantiated by its driver connection. */
    public function __construct(SQLite3 $connection, SQLite3Stmt $statement)
    {
        $this->connection = $connection;
        $this->statement  = $statement;
    }

    /**
     * @throws UnknownParameterType
     *
     * {@inheritDoc}
     *
     * @phpstan-assert ParameterType::* $type
     */
    public function bindValue($param, $value, $type = ParameterType::STRING): bool
    {
        if (func_num_args() < 3) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5558',
                'Not passing $type to Statement::bindValue() is deprecated.'
                . ' Pass the type corresponding to the parameter being bound.',
            );
        }

        return $this->statement->bindValue($param, $value, $this->convertParamType($type));
    }

    /**
     * @throws UnknownParameterType
     *
     * {@inheritDoc}
     *
     * @phpstan-assert ParameterType::* $type
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null): bool
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5563',
            '%s is deprecated. Use bindValue() instead.',
            __METHOD__,
        );

        if (func_num_args() < 3) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5558',
                'Not passing $type to Statement::bindParam() is deprecated.'
                . ' Pass the type corresponding to the parameter being bound.',
            );
        }

        return $this->statement->bindParam($param, $variable, $this->convertParamType($type));
    }

    /** @inheritDoc */
    public function execute($params = null): Result
    {
        if ($params !== null) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5556',
                'Passing $params to Statement::execute() is deprecated. Bind parameters using'
                . ' Statement::bindParam() or Statement::bindValue() instead.',
            );

            foreach ($params as $param => $value) {
                if (is_int($param)) {
                    $this->bindValue($param + 1, $value, ParameterType::STRING);
                } else {
                    $this->bindValue($param, $value, ParameterType::STRING);
                }
            }
        }

        try {
            $result = $this->statement->execute();
        } catch (\Exception $e) {
            throw Exception::new($e);
        }

        assert($result !== false);

        return new Result($result, $this->connection->changes());
    }

    /**
     * @phpstan-return value-of<self::PARAM_TYPE_MAP>
     *
     * @phpstan-assert ParameterType::* $type
     */
    private function convertParamType(int $type): int
    {
        if (! isset(self::PARAM_TYPE_MAP[$type])) {
            throw UnknownParameterType::new($type);
        }

        return self::PARAM_TYPE_MAP[$type];
    }
}
