<?php

namespace Safe;

use Safe\Exceptions\MysqlndQcException;

/**
 * Flush all cache contents.
 *
 * Flushing the cache is a storage handler responsibility.
 * All built-in storage handler but the
 * memcache storage
 * handler support flushing the cache. The
 * memcache
 * storage handler cannot flush its cache contents.
 *
 * User-defined storage handler may or may not support the operation.
 *
 * @throws MysqlndQcException
 *
 */
function mysqlnd_qc_clear_cache(): void
{
    error_clear_last();
    $result = \mysqlnd_qc_clear_cache();
    if ($result === false) {
        throw MysqlndQcException::createFromPhpError();
    }
}


/**
 * Installs a callback which decides whether a statement is cached.
 *
 * There are several ways of hinting PELC/mysqlnd_qc to cache a query.
 * By default, PECL/mysqlnd_qc attempts to cache a if caching of all statements
 * is enabled or the query string begins with a certain SQL hint.
 * The plugin internally calls a function named is_select()
 * to find out. This internal function can be replaced with a user-defined callback.
 * Then, the user-defined callback is responsible to decide whether the plugin
 * attempts to cache a statement. Because the internal function is replaced
 * with the callback, the callback gains full control. The callback is free
 * to ignore the configuration setting mysqlnd_qc.cache_by_default
 * and SQL hints.
 *
 * The callback is invoked for every statement inspected by the plugin.
 * It is given the statements string as a parameter. The callback returns
 * FALSE if the statement shall not be cached. It returns TRUE to
 * make the plugin attempt to cache the statements result set, if any.
 * A so-created cache entry is given the default TTL set with the
 * PHP configuration directive mysqlnd_qc.ttl.
 * If a different TTL shall be used, the callback returns a numeric
 * value to be used as the TTL.
 *
 * The internal is_select function is part of the internal
 * cache storage handler interface. Thus, a user-defined storage handler
 * offers the same capabilities.
 *
 * @param string $callback
 * @return mixed Returns TRUE on success.
 * @throws MysqlndQcException
 *
 */
function mysqlnd_qc_set_is_select(string $callback)
{
    error_clear_last();
    $result = \mysqlnd_qc_set_is_select($callback);
    if ($result === false) {
        throw MysqlndQcException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets the storage handler used by the query cache. A list of available
 * storage handler can be obtained from
 * mysqlnd_qc_get_available_handlers.
 * Which storage are available depends on the compile time
 * configuration of the query cache plugin. The
 * default storage handler is always available.
 * All other storage handler must be enabled explicitly when building the
 * extension.
 *
 * @param string $handler Handler can be of type string representing the name of a
 * built-in storage handler or an object of type
 * mysqlnd_qc_handler_default.
 * The names of the built-in storage handler are
 * default,
 * APC,
 * MEMCACHE,
 * sqlite.
 * @throws MysqlndQcException
 *
 */
function mysqlnd_qc_set_storage_handler(string $handler): void
{
    error_clear_last();
    $result = \mysqlnd_qc_set_storage_handler($handler);
    if ($result === false) {
        throw MysqlndQcException::createFromPhpError();
    }
}
