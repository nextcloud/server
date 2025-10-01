<?php

namespace Doctrine\DBAL\Driver\PDO\SQLSrv;

use Doctrine\DBAL\Driver\Exception\UnknownParameterType;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\PDO\Statement as PDOStatement;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use PDO;

use function func_num_args;

final class Statement extends AbstractStatementMiddleware
{
    private PDOStatement $statement;

    /** @internal The statement can be only instantiated by its driver connection. */
    public function __construct(PDOStatement $statement)
    {
        parent::__construct($statement);

        $this->statement = $statement;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@see bindValue()} instead.
     *
     * @param string|int $param
     * @param mixed      $variable
     * @param int        $type
     * @param int|null   $length
     * @param mixed      $driverOptions The usage of the argument is deprecated.
     *
     * @throws UnknownParameterType
     *
     * @phpstan-assert ParameterType::* $type
     */
    public function bindParam(
        $param,
        &$variable,
        $type = ParameterType::STRING,
        $length = null,
        $driverOptions = null
    ): bool {
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

        switch ($type) {
            case ParameterType::LARGE_OBJECT:
            case ParameterType::BINARY:
                $driverOptions ??= PDO::SQLSRV_ENCODING_BINARY;

                break;

            case ParameterType::ASCII:
                $type          = ParameterType::STRING;
                $length        = 0;
                $driverOptions = PDO::SQLSRV_ENCODING_SYSTEM;
                break;
        }

        return $this->statement->bindParam($param, $variable, $type, $length ?? 0, $driverOptions);
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

        return $this->bindParam($param, $value, $type);
    }
}
