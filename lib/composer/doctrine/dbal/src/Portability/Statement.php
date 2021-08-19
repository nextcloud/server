<?php

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;

/**
 * Portability wrapper for a Statement.
 */
final class Statement implements DriverStatement
{
    /** @var DriverStatement */
    private $stmt;

    /** @var Converter */
    private $converter;

    /**
     * Wraps <tt>Statement</tt> and applies portability measures.
     */
    public function __construct(DriverStatement $stmt, Converter $converter)
    {
        $this->stmt      = $stmt;
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        return $this->stmt->bindParam($param, $variable, $type, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        return $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): ResultInterface
    {
        return new Result(
            $this->stmt->execute($params),
            $this->converter
        );
    }
}
