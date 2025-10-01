<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\IBMDB2\Exception;

use Doctrine\DBAL\Driver\AbstractException;

use function db2_conn_error;
use function db2_conn_errormsg;

/** @internal */
final class ConnectionFailed extends AbstractException
{
    public static function new(): self
    {
        $message  = db2_conn_errormsg();
        $sqlState = db2_conn_error();

        return Factory::create($message, static function (int $code) use ($message, $sqlState): self {
            return new self($message, $sqlState, $code);
        });
    }
}
