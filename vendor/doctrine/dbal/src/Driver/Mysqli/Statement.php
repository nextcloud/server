<?php

namespace Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\Exception\UnknownParameterType;
use Doctrine\DBAL\Driver\Mysqli\Exception\FailedReadingStreamOffset;
use Doctrine\DBAL\Driver\Mysqli\Exception\NonStreamResourceUsedAsLargeObject;
use Doctrine\DBAL\Driver\Mysqli\Exception\StatementError;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use mysqli_sql_exception;
use mysqli_stmt;

use function array_fill;
use function assert;
use function count;
use function feof;
use function fread;
use function func_num_args;
use function get_resource_type;
use function is_int;
use function is_resource;
use function str_repeat;

final class Statement implements StatementInterface
{
    private const PARAM_TYPE_MAP = [
        ParameterType::ASCII => 's',
        ParameterType::STRING => 's',
        ParameterType::BINARY => 's',
        ParameterType::BOOLEAN => 'i',
        ParameterType::NULL => 's',
        ParameterType::INTEGER => 'i',
        ParameterType::LARGE_OBJECT => 'b',
    ];

    private mysqli_stmt $stmt;

    /** @var mixed[] */
    private array $boundValues;

    private string $types;

    /**
     * Contains ref values for bindValue().
     *
     * @var mixed[]
     */
    private array $values = [];

    /** @internal The statement can be only instantiated by its driver connection. */
    public function __construct(mysqli_stmt $stmt)
    {
        $this->stmt = $stmt;

        $paramCount        = $this->stmt->param_count;
        $this->types       = str_repeat('s', $paramCount);
        $this->boundValues = array_fill(1, $paramCount, null);
    }

    public function __destruct()
    {
        @$this->stmt->close();
    }

    /**
     * @deprecated Use {@see bindValue()} instead.
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

        assert(is_int($param));

        if (func_num_args() < 3) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5558',
                'Not passing $type to Statement::bindParam() is deprecated.'
                    . ' Pass the type corresponding to the parameter being bound.',
            );
        }

        if (! isset(self::PARAM_TYPE_MAP[$type])) {
            throw UnknownParameterType::new($type);
        }

        $this->boundValues[$param] =& $variable;
        $this->types[$param - 1]   = self::PARAM_TYPE_MAP[$type];

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-assert ParameterType::* $type
     */
    public function bindValue($param, $value, $type = ParameterType::STRING): bool
    {
        assert(is_int($param));

        if (func_num_args() < 3) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5558',
                'Not passing $type to Statement::bindValue() is deprecated.'
                    . ' Pass the type corresponding to the parameter being bound.',
            );
        }

        if (! isset(self::PARAM_TYPE_MAP[$type])) {
            throw UnknownParameterType::new($type);
        }

        $this->values[$param]      = $value;
        $this->boundValues[$param] =& $this->values[$param];
        $this->types[$param - 1]   = self::PARAM_TYPE_MAP[$type];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($params = null): ResultInterface
    {
        if ($params !== null) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5556',
                'Passing $params to Statement::execute() is deprecated. Bind parameters using'
                    . ' Statement::bindParam() or Statement::bindValue() instead.',
            );
        }

        if ($params !== null && count($params) > 0) {
            if (! $this->bindUntypedValues($params)) {
                throw StatementError::new($this->stmt);
            }
        } elseif (count($this->boundValues) > 0) {
            $this->bindTypedParameters();
        }

        try {
            $result = $this->stmt->execute();
        } catch (mysqli_sql_exception $e) {
            throw StatementError::upcast($e);
        }

        if (! $result) {
            throw StatementError::new($this->stmt);
        }

        return new Result($this->stmt, $this);
    }

    /**
     * Binds parameters with known types previously bound to the statement
     *
     * @throws Exception
     */
    private function bindTypedParameters(): void
    {
        $streams = $values = [];
        $types   = $this->types;

        foreach ($this->boundValues as $parameter => $value) {
            assert(is_int($parameter));

            if (! isset($types[$parameter - 1])) {
                $types[$parameter - 1] = self::PARAM_TYPE_MAP[ParameterType::STRING];
            }

            if ($types[$parameter - 1] === self::PARAM_TYPE_MAP[ParameterType::LARGE_OBJECT]) {
                if (is_resource($value)) {
                    if (get_resource_type($value) !== 'stream') {
                        throw NonStreamResourceUsedAsLargeObject::new($parameter);
                    }

                    $streams[$parameter] = $value;
                    $values[$parameter]  = null;
                    continue;
                }

                $types[$parameter - 1] = self::PARAM_TYPE_MAP[ParameterType::STRING];
            }

            $values[$parameter] = $value;
        }

        if (! $this->stmt->bind_param($types, ...$values)) {
            throw StatementError::new($this->stmt);
        }

        $this->sendLongData($streams);
    }

    /**
     * Handle $this->_longData after regular query parameters have been bound
     *
     * @param array<int, resource> $streams
     *
     * @throws Exception
     */
    private function sendLongData(array $streams): void
    {
        foreach ($streams as $paramNr => $stream) {
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);

                if ($chunk === false) {
                    throw FailedReadingStreamOffset::new($paramNr);
                }

                if (! $this->stmt->send_long_data($paramNr - 1, $chunk)) {
                    throw StatementError::new($this->stmt);
                }
            }
        }
    }

    /**
     * Binds a array of values to bound parameters.
     *
     * @param mixed[] $values
     */
    private function bindUntypedValues(array $values): bool
    {
        return $this->stmt->bind_param(str_repeat('s', count($values)), ...$values);
    }
}
