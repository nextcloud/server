<?php

namespace Doctrine\DBAL\Portability;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result as DriverResult;
use Doctrine\DBAL\Driver\Statement as DriverStatement;

/**
 * Portability wrapper for a Connection.
 */
final class Connection extends AbstractConnectionMiddleware
{
    public const PORTABILITY_ALL           = 255;
    public const PORTABILITY_NONE          = 0;
    public const PORTABILITY_RTRIM         = 1;
    public const PORTABILITY_EMPTY_TO_NULL = 4;
    public const PORTABILITY_FIX_CASE      = 8;

    private Converter $converter;

    public function __construct(ConnectionInterface $connection, Converter $converter)
    {
        parent::__construct($connection);

        $this->converter = $converter;
    }

    public function prepare(string $sql): DriverStatement
    {
        return new Statement(
            parent::prepare($sql),
            $this->converter,
        );
    }

    public function query(string $sql): DriverResult
    {
        return new Result(
            parent::query($sql),
            $this->converter,
        );
    }
}
