<?php

namespace Doctrine\DBAL\Driver\PgSQL;

use Doctrine\DBAL\Driver\PgSQL\Exception\UnknownParameter;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use PgSql\Connection as PgSqlConnection;
use TypeError;

use function assert;
use function func_num_args;
use function get_class;
use function gettype;
use function is_int;
use function is_object;
use function is_resource;
use function ksort;
use function pg_escape_bytea;
use function pg_escape_identifier;
use function pg_get_result;
use function pg_last_error;
use function pg_query;
use function pg_result_error;
use function pg_send_execute;
use function sprintf;
use function stream_get_contents;

final class Statement implements StatementInterface
{
    /** @var PgSqlConnection|resource */
    private $connection;

    private string $name;

    /** @var array<array-key, int> */
    private array $parameterMap;

    /** @var array<int, mixed> */
    private array $parameters = [];

    /** @var array<int, int> */
    private array $parameterTypes = [];

    /**
     * @param PgSqlConnection|resource $connection
     * @param array<array-key, int>    $parameterMap
     */
    public function __construct($connection, string $name, array $parameterMap)
    {
        if (! is_resource($connection) && ! $connection instanceof PgSqlConnection) {
            throw new TypeError(sprintf(
                'Expected connection to be a resource or an instance of %s, got %s.',
                PgSqlConnection::class,
                is_object($connection) ? get_class($connection) : gettype($connection),
            ));
        }

        $this->connection   = $connection;
        $this->name         = $name;
        $this->parameterMap = $parameterMap;
    }

    public function __destruct()
    {
        if (! isset($this->connection)) {
            return;
        }

        @pg_query(
            $this->connection,
            'DEALLOCATE ' . pg_escape_identifier($this->connection, $this->name),
        );
    }

    /** {@inheritDoc} */
    public function bindValue($param, $value, $type = ParameterType::STRING): bool
    {
        if (! isset($this->parameterMap[$param])) {
            throw UnknownParameter::new((string) $param);
        }

        if ($value === null) {
            $type = ParameterType::NULL;
        }

        if ($type === ParameterType::BOOLEAN) {
            $this->parameters[$this->parameterMap[$param]]     = (bool) $value === false ? 'f' : 't';
            $this->parameterTypes[$this->parameterMap[$param]] = ParameterType::STRING;
        } else {
            $this->parameters[$this->parameterMap[$param]]     = $value;
            $this->parameterTypes[$this->parameterMap[$param]] = $type;
        }

        return true;
    }

    /** {@inheritDoc} */
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

        if (func_num_args() > 4) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/4533',
                'The $driverOptions argument of Statement::bindParam() is deprecated.',
            );
        }

        if (! isset($this->parameterMap[$param])) {
            throw UnknownParameter::new((string) $param);
        }

        $this->parameters[$this->parameterMap[$param]]     = &$variable;
        $this->parameterTypes[$this->parameterMap[$param]] = $type;

        return true;
    }

    /** {@inheritDoc} */
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

        ksort($this->parameters);

        $escapedParameters = [];
        foreach ($this->parameters as $parameter => $value) {
            switch ($this->parameterTypes[$parameter]) {
                case ParameterType::BINARY:
                case ParameterType::LARGE_OBJECT:
                    $escapedParameters[] = $value === null ? null : pg_escape_bytea(
                        $this->connection,
                        is_resource($value) ? stream_get_contents($value) : $value,
                    );
                    break;
                default:
                    $escapedParameters[] = $value;
            }
        }

        if (@pg_send_execute($this->connection, $this->name, $escapedParameters) !== true) {
            throw new Exception(pg_last_error($this->connection));
        }

        $result = @pg_get_result($this->connection);
        assert($result !== false);

        if ((bool) pg_result_error($result)) {
            throw Exception::fromResult($result);
        }

        return new Result($result);
    }
}
