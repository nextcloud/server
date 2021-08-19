<?php

namespace Safe;

use Safe\Exceptions\MysqlndMsException;

/**
 * Returns a list of currently configured servers.
 *
 * @param mixed $connection A MySQL connection handle obtained from any of the
 * connect functions of the mysqli,
 * mysql or
 * PDO_MYSQL extensions.
 * @return array FALSE on error. Otherwise, returns an array with two entries
 * masters and slaves each of which contains
 * an array listing all corresponding servers.
 *
 * The function can be used to check and debug the list of servers currently
 * used by the plugin. It is mostly useful when the list of servers changes at
 * runtime, for example, when using MySQL Fabric.
 *
 * masters and slaves server entries
 * @throws MysqlndMsException
 *
 */
function mysqlnd_ms_dump_servers($connection): array
{
    error_clear_last();
    $result = \mysqlnd_ms_dump_servers($connection);
    if ($result === false) {
        throw MysqlndMsException::createFromPhpError();
    }
    return $result;
}


/**
 * MySQL Fabric related.
 *
 * Switch the connection to the nodes handling global sharding queries
 * for the given table name.
 *
 * @param mixed $connection A MySQL connection handle obtained from any of the
 * connect functions of the mysqli,
 * mysql or
 * PDO_MYSQL extensions.
 * @param mixed $table_name The table name to ask Fabric about.
 * @return array FALSE on error. Otherwise, TRUE
 * @throws MysqlndMsException
 *
 */
function mysqlnd_ms_fabric_select_global($connection, $table_name): array
{
    error_clear_last();
    $result = \mysqlnd_ms_fabric_select_global($connection, $table_name);
    if ($result === false) {
        throw MysqlndMsException::createFromPhpError();
    }
    return $result;
}


/**
 * MySQL Fabric related.
 *
 * Switch the connection to the shards responsible for the
 * given table name and shard key.
 *
 * @param mixed $connection A MySQL connection handle obtained from any of the
 * connect functions of the mysqli,
 * mysql or
 * PDO_MYSQL extensions.
 * @param mixed $table_name The table name to ask Fabric about.
 * @param mixed $shard_key The shard key to ask Fabric about.
 * @return array FALSE on error. Otherwise, TRUE
 * @throws MysqlndMsException
 *
 */
function mysqlnd_ms_fabric_select_shard($connection, $table_name, $shard_key): array
{
    error_clear_last();
    $result = \mysqlnd_ms_fabric_select_shard($connection, $table_name, $shard_key);
    if ($result === false) {
        throw MysqlndMsException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns an array which describes the last used connection from the plugins
 * connection pool currently pointed to by the user connection handle. If using the
 * plugin, a user connection handle represents a pool of database connections.
 * It is not possible to tell from the user connection handles properties to which
 * database server from the pool the user connection handle points.
 *
 * The function can be used to debug or monitor PECL mysqlnd_ms.
 *
 * @param mixed $connection A MySQL connection handle obtained from any of the
 * connect functions of the mysqli,
 * mysql or
 * PDO_MYSQL extensions.
 * @return array FALSE on error. Otherwise, an
 * array which describes the connection used to
 * execute the last statement on.
 *
 * Array which describes the connection.
 * @throws MysqlndMsException
 *
 */
function mysqlnd_ms_get_last_used_connection($connection): array
{
    error_clear_last();
    $result = \mysqlnd_ms_get_last_used_connection($connection);
    if ($result === false) {
        throw MysqlndMsException::createFromPhpError();
    }
    return $result;
}
