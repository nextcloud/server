<?php

namespace Doctrine\DBAL\Driver\PDO;

use Doctrine\DBAL\Driver\Exception as ExceptionInterface;
use Doctrine\DBAL\Driver\Exception\UnknownParameterType;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use PDOStatement;

use function array_slice;
use function func_get_args;

final class Statement implements StatementInterface
{
    private const PARAM_TYPE_MAP = [
        ParameterType::NULL => PDO::PARAM_NULL,
        ParameterType::INTEGER => PDO::PARAM_INT,
        ParameterType::STRING => PDO::PARAM_STR,
        ParameterType::ASCII => PDO::PARAM_STR,
        ParameterType::BINARY => PDO::PARAM_LOB,
        ParameterType::LARGE_OBJECT => PDO::PARAM_LOB,
        ParameterType::BOOLEAN => PDO::PARAM_BOOL,
    ];

    /** @var PDOStatement */
    private $stmt;

    /**
     * @internal The statement can be only instantiated by its driver connection.
     */
    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $type = $this->convertParamType($type);

        try {
            return $this->stmt->bindValue($param, $value, $type);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed    $param
     * @param mixed    $variable
     * @param int      $type
     * @param int|null $length
     * @param mixed    $driverOptions
     *
     * @return bool
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null, $driverOptions = null)
    {
        $type = $this->convertParamType($type);

        try {
            return $this->stmt->bindParam($param, $variable, $type, ...array_slice(func_get_args(), 3));
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): ResultInterface
    {
        try {
            $this->stmt->execute($params);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }

        return new Result($this->stmt);
    }

    /**
     * Converts DBAL parameter type to PDO parameter type
     *
     * @param int $type Parameter type
     *
     * @throws ExceptionInterface
     */
    private function convertParamType(int $type): int
    {
        if (! isset(self::PARAM_TYPE_MAP[$type])) {
            throw UnknownParameterType::new($type);
        }

        return self::PARAM_TYPE_MAP[$type];
    }
}
