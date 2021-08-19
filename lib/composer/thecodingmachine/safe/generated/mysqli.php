<?php

namespace Safe;

use Safe\Exceptions\MysqliException;

/**
 * Returns an empty array.
 * Available only with mysqlnd.
 *
 * @return array Returns an empty array on success, FALSE otherwise.
 * @throws MysqliException
 *
 */
function mysqli_get_cache_stats(): array
{
    error_clear_last();
    $result = \mysqli_get_cache_stats();
    if ($result === false) {
        throw MysqliException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns client per-process statistics.
 * Available only with mysqlnd.
 *
 * @return array Returns an array with client stats if success, FALSE otherwise.
 * @throws MysqliException
 *
 */
function mysqli_get_client_stats(): array
{
    error_clear_last();
    $result = \mysqli_get_client_stats();
    if ($result === false) {
        throw MysqliException::createFromPhpError();
    }
    return $result;
}
