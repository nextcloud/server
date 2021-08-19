<?php

namespace Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\Exception\UnknownParameterType;
use Doctrine\DBAL\Driver\Mysqli\Exception\ConnectionError;
use Doctrine\DBAL\Driver\Mysqli\Exception\FailedReadingStreamOffset;
use Doctrine\DBAL\Driver\Mysqli\Exception\NonStreamResourceUsedAsLargeObject;
use Doctrine\DBAL\Driver\Mysqli\Exception\StatementError;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use mysqli;
use mysqli_stmt;

use function array_fill;
use function assert;
use function count;
use function feof;
use function fread;
use function get_resource_type;
use function is_int;
use function is_resource;
use function str_repeat;

final class Statement implements StatementInterface
{
    /** @var string[] */
    protected static $_paramTypeMap = [
        ParameterType::ASCII => 's',
        ParameterType::STRING => 's',
        ParameterType::BINARY => 's',
        ParameterType::BOOLEAN => 'i',
        ParameterType::NULL => 's',
        ParameterType::INTEGER => 'i',
        ParameterType::LARGE_OBJECT => 'b',
    ];

    /** @var mysqli */
    protected $_conn;

    /** @var mysqli_stmt */
    protected $_stmt;

    /** @var mixed[] */
    protected $_bindedValues;

    /** @var string */
    protected $types;

    /**
     * Contains ref values for bindValue().
     *
     * @var mixed[]
     */
    protected $_values = [];

    /**
     * @internal The statement can be only instantiated by its driver connection.
     *
     * @param string $prepareString
     *
     * @throws Exception
     */
    public function __construct(mysqli $conn, $prepareString)
    {
        $this->_conn = $conn;

        $stmt = $conn->prepare($prepareString);

        if ($stmt === false) {
            throw ConnectionError::new($this->_conn);
        }

        $this->_stmt = $stmt;

        $paramCount = $this->_stmt->param_count;
        if (0 >= $paramCount) {
            return;
        }

        $this->types         = str_repeat('s', $paramCount);
        $this->_bindedValues = array_fill(1, $paramCount, null);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        assert(is_int($param));

        if (! isset(self::$_paramTypeMap[$type])) {
            throw UnknownParameterType::new($type);
        }

        $this->_bindedValues[$param] =& $variable;
        $this->types[$param - 1]     = self::$_paramTypeMap[$type];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        assert(is_int($param));

        if (! isset(self::$_paramTypeMap[$type])) {
            throw UnknownParameterType::new($type);
        }

        $this->_values[$param]       = $value;
        $this->_bindedValues[$param] =& $this->_values[$param];
        $this->types[$param - 1]     = self::$_paramTypeMap[$type];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): ResultInterface
    {
        if ($this->_bindedValues !== null) {
            if ($params !== null) {
                if (! $this->bindUntypedValues($params)) {
                    throw StatementError::new($this->_stmt);
                }
            } else {
                $this->bindTypedParameters();
            }
        }

        if (! $this->_stmt->execute()) {
            throw StatementError::new($this->_stmt);
        }

        return new Result($this->_stmt);
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

        foreach ($this->_bindedValues as $parameter => $value) {
            assert(is_int($parameter));

            if (! isset($types[$parameter - 1])) {
                $types[$parameter - 1] = static::$_paramTypeMap[ParameterType::STRING];
            }

            if ($types[$parameter - 1] === static::$_paramTypeMap[ParameterType::LARGE_OBJECT]) {
                if (is_resource($value)) {
                    if (get_resource_type($value) !== 'stream') {
                        throw NonStreamResourceUsedAsLargeObject::new($parameter);
                    }

                    $streams[$parameter] = $value;
                    $values[$parameter]  = null;
                    continue;
                }

                $types[$parameter - 1] = static::$_paramTypeMap[ParameterType::STRING];
            }

            $values[$parameter] = $value;
        }

        if (! $this->_stmt->bind_param($types, ...$values)) {
            throw StatementError::new($this->_stmt);
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

                if (! $this->_stmt->send_long_data($paramNr - 1, $chunk)) {
                    throw StatementError::new($this->_stmt);
                }
            }
        }
    }

    /**
     * Binds a array of values to bound parameters.
     *
     * @param mixed[] $values
     *
     * @return bool
     */
    private function bindUntypedValues(array $values)
    {
        $params = [];
        $types  = str_repeat('s', count($values));

        foreach ($values as &$v) {
            $params[] =& $v;
        }

        return $this->_stmt->bind_param($types, ...$params);
    }
}
