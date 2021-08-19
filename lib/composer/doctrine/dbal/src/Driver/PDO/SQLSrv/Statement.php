<?php

namespace Doctrine\DBAL\Driver\PDO\SQLSrv;

use Doctrine\DBAL\Driver\PDO\Statement as PDOStatement;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;

final class Statement implements StatementInterface
{
    /** @var PDOStatement */
    private $statement;

    /**
     * @internal The statement can be only instantiated by its driver connection.
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $driverOptions
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null, $driverOptions = null)
    {
        switch ($type) {
            case ParameterType::LARGE_OBJECT:
            case ParameterType::BINARY:
                if ($driverOptions === null) {
                    $driverOptions = PDO::SQLSRV_ENCODING_BINARY;
                }

                break;

            case ParameterType::ASCII:
                $type          = ParameterType::STRING;
                $length        = 0;
                $driverOptions = PDO::SQLSRV_ENCODING_SYSTEM;
                break;
        }

        return $this->statement->bindParam($param, $variable, $type, $length, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        return $this->bindParam($param, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): Result
    {
        return $this->statement->execute($params);
    }
}
