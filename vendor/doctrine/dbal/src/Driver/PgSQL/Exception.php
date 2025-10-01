<?php

namespace Doctrine\DBAL\Driver\PgSQL;

use Doctrine\DBAL\Driver\AbstractException;
use PgSql\Result as PgSqlResult;

use function pg_result_error_field;

use const PGSQL_DIAG_MESSAGE_PRIMARY;
use const PGSQL_DIAG_SQLSTATE;

/** @internal */
final class Exception extends AbstractException
{
    /** @param PgSqlResult|resource $result */
    public static function fromResult($result): self
    {
        $sqlstate = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);
        if ($sqlstate === false) {
            $sqlstate = null;
        }

        return new self((string) pg_result_error_field($result, PGSQL_DIAG_MESSAGE_PRIMARY), $sqlstate);
    }
}
