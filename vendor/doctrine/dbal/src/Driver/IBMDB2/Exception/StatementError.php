<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\IBMDB2\Exception;

use Doctrine\DBAL\Driver\AbstractException;

use function db2_stmt_error;
use function db2_stmt_errormsg;

/** @internal */
final class StatementError extends AbstractException
{
    /** @param resource|null $statement */
    public static function new($statement = null): self
    {
        if ($statement !== null) {
            $message  = db2_stmt_errormsg($statement);
            $sqlState = db2_stmt_error($statement);
        } else {
            $message  = db2_stmt_errormsg();
            $sqlState = db2_stmt_error();
        }

        return Factory::create($message, static function (int $code) use ($message, $sqlState): self {
            return new self($message, $sqlState, $code);
        });
    }
}
