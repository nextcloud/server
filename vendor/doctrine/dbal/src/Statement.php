<?php

namespace Doctrine\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;

use function func_num_args;
use function is_string;

/**
 * A database abstraction-level statement that implements support for logging, DBAL mapping types, etc.
 */
class Statement
{
    /**
     * The SQL statement.
     *
     * @var string
     */
    protected $sql;

    /**
     * The bound parameters.
     *
     * @var mixed[]
     */
    protected $params = [];

    /**
     * The parameter types.
     *
     * @var int[]|string[]
     */
    protected $types = [];

    /**
     * The underlying driver statement.
     *
     * @var Driver\Statement
     */
    protected $stmt;

    /**
     * The underlying database platform.
     *
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * The connection this statement is bound to and executed on.
     *
     * @var Connection
     */
    protected $conn;

    /**
     * Creates a new <tt>Statement</tt> for the given SQL and <tt>Connection</tt>.
     *
     * @internal The statement can be only instantiated by {@see Connection}.
     *
     * @param Connection       $conn      The connection for handling statement errors.
     * @param Driver\Statement $statement The underlying driver-level statement.
     * @param string           $sql       The SQL of the statement.
     *
     * @throws Exception
     */
    public function __construct(Connection $conn, Driver\Statement $statement, string $sql)
    {
        $this->conn     = $conn;
        $this->stmt     = $statement;
        $this->sql      = $sql;
        $this->platform = $conn->getDatabasePlatform();
    }

    /**
     * Binds a parameter value to the statement.
     *
     * The value can optionally be bound with a DBAL mapping type.
     * If bound with a DBAL mapping type, the binding type is derived from the mapping
     * type and the value undergoes the conversion routines of the mapping type before
     * being bound.
     *
     * @param string|int $param The name or position of the parameter.
     * @param mixed      $value The value of the parameter.
     * @param mixed      $type  Either a PDO binding type or a DBAL mapping type name or instance.
     *
     * @return bool TRUE on success, FALSE on failure.
     *
     * @throws Exception
     */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->params[$param] = $value;
        $this->types[$param]  = $type;

        $bindingType = ParameterType::STRING;

        if ($type !== null) {
            if (is_string($type)) {
                $type = Type::getType($type);
            }

            $bindingType = $type;

            if ($type instanceof Type) {
                $value       = $type->convertToDatabaseValue($value, $this->platform);
                $bindingType = $type->getBindingType();
            }
        }

        try {
            return $this->stmt->bindValue($param, $value, $bindingType);
        } catch (Driver\Exception $e) {
            throw $this->conn->convertException($e);
        }
    }

    /**
     * Binds a parameter to a value by reference.
     *
     * Binding a parameter by reference does not support DBAL mapping types.
     *
     * @deprecated Use {@see bindValue()} instead.
     *
     * @param string|int $param    The name or position of the parameter.
     * @param mixed      $variable The reference to the variable to bind.
     * @param int        $type     The binding type.
     * @param int|null   $length   Must be specified when using an OUT bind
     *                             so that PHP allocates enough memory to hold the returned value.
     *
     * @return bool TRUE on success, FALSE on failure.
     *
     * @throws Exception
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5563',
            '%s is deprecated. Use bindValue() instead.',
            __METHOD__,
        );

        $this->params[$param] = $variable;
        $this->types[$param]  = $type;

        try {
            if (func_num_args() > 3) {
                return $this->stmt->bindParam($param, $variable, $type, $length);
            }

            return $this->stmt->bindParam($param, $variable, $type);
        } catch (Driver\Exception $e) {
            throw $this->conn->convertException($e);
        }
    }

    /**
     * Executes the statement with the currently bound parameters.
     *
     * @deprecated Statement::execute() is deprecated, use Statement::executeQuery() or executeStatement() instead
     *
     * @param mixed[]|null $params
     *
     * @throws Exception
     */
    public function execute($params = null): Result
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4580',
            '%s() is deprecated, use Statement::executeQuery() or Statement::executeStatement() instead',
            __METHOD__,
        );

        if ($params !== null) {
            $this->params = $params;
        }

        $logger = $this->conn->getConfiguration()->getSQLLogger();
        if ($logger !== null) {
            $logger->startQuery($this->sql, $this->params, $this->types);
        }

        try {
            return new Result(
                $this->stmt->execute($params),
                $this->conn,
            );
        } catch (Driver\Exception $ex) {
            throw $this->conn->convertExceptionDuringQuery($ex, $this->sql, $this->params, $this->types);
        } finally {
            if ($logger !== null) {
                $logger->stopQuery();
            }
        }
    }

    /**
     * Executes the statement with the currently bound parameters and return result.
     *
     * @param mixed[] $params
     *
     * @throws Exception
     */
    public function executeQuery(array $params = []): Result
    {
        if (func_num_args() > 0) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5556',
                'Passing $params to Statement::executeQuery() is deprecated. Bind parameters using'
                    . ' Statement::bindParam() or Statement::bindValue() instead.',
            );
        }

        if ($params === []) {
            $params = null; // Workaround as long execute() exists and used internally.
        }

        return $this->execute($params);
    }

    /**
     * Executes the statement with the currently bound parameters and return affected rows.
     *
     * @param mixed[] $params
     *
     * @throws Exception
     */
    public function executeStatement(array $params = []): int
    {
        if (func_num_args() > 0) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5556',
                'Passing $params to Statement::executeStatement() is deprecated. Bind parameters using'
                    . ' Statement::bindParam() or Statement::bindValue() instead.',
            );
        }

        if ($params === []) {
            $params = null; // Workaround as long execute() exists and used internally.
        }

        return $this->execute($params)->rowCount();
    }

    /**
     * Gets the wrapped driver statement.
     *
     * @return Driver\Statement
     */
    public function getWrappedStatement()
    {
        return $this->stmt;
    }
}
