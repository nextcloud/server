<?php

namespace Safe;

use Safe\Exceptions\MsqlException;

/**
 * Returns number of affected rows by the last SELECT, UPDATE or DELETE
 * query associated with result.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * msql_query.
 * @return int Returns the number of affected rows on success.
 * @throws MsqlException
 *
 */
function msql_affected_rows($result): int
{
    error_clear_last();
    $result = \msql_affected_rows($result);
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * msql_close closes the non-persistent connection to
 * the mSQL server that's associated with the specified link identifier.
 *
 * Using msql_close isn't usually necessary, as
 * non-persistent open links are automatically closed at the end of the
 * script's execution. See also freeing resources.
 *
 * @param resource|null $link_identifier The mSQL connection.
 * If not specified, the last link opened by msql_connect
 * is assumed. If no such link is found, the function will try to establish a
 * link as if msql_connect was called, and use it.
 * @throws MsqlException
 *
 */
function msql_close($link_identifier = null): void
{
    error_clear_last();
    if ($link_identifier !== null) {
        $result = \msql_close($link_identifier);
    } else {
        $result = \msql_close();
    }
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
}


/**
 * msql_connect establishes a connection to a mSQL
 * server.
 *
 * If a second call is made to msql_connect with
 * the same arguments, no new link will be established, but instead, the
 * link identifier of the already opened link will be returned.
 *
 * The link to the server will be closed as soon as the execution of the
 * script ends, unless it's closed earlier by explicitly calling
 * msql_close.
 *
 * @param string $hostname The hostname can also include a port number. e.g.
 * hostname,port.
 *
 * If not specified, the connection is established by the means of a Unix
 * domain socket, being then more efficient then a localhost TCP socket
 * connection.
 *
 * While this function will accept a colon (:) as a
 * host/port separator, a comma (,) is the preferred
 * method.
 * @return resource Returns a positive mSQL link identifier on success.
 * @throws MsqlException
 *
 */
function msql_connect(string $hostname = null)
{
    error_clear_last();
    if ($hostname !== null) {
        $result = \msql_connect($hostname);
    } else {
        $result = \msql_connect();
    }
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * msql_create_db attempts to create a new database on
 * the mSQL server.
 *
 * @param string $database_name The name of the mSQL database.
 * @param resource|null $link_identifier The mSQL connection.
 * If not specified, the last link opened by msql_connect
 * is assumed. If no such link is found, the function will try to establish a
 * link as if msql_connect was called, and use it.
 * @throws MsqlException
 *
 */
function msql_create_db(string $database_name, $link_identifier = null): void
{
    error_clear_last();
    if ($link_identifier !== null) {
        $result = \msql_create_db($database_name, $link_identifier);
    } else {
        $result = \msql_create_db($database_name);
    }
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
}


/**
 * msql_data_seek moves the internal row
 * pointer of the mSQL result associated with the specified query
 * identifier to point to the specified row number.  The next call
 * to msql_fetch_row would return that
 * row.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * msql_query.
 * @param int $row_number The seeked row number.
 * @throws MsqlException
 *
 */
function msql_data_seek($result, int $row_number): void
{
    error_clear_last();
    $result = \msql_data_seek($result, $row_number);
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
}


/**
 * msql_db_query selects a database and executes a query
 * on it.
 *
 * @param string $database The name of the mSQL database.
 * @param string $query The SQL query.
 * @param resource|null $link_identifier The mSQL connection.
 * If not specified, the last link opened by msql_connect
 * is assumed. If no such link is found, the function will try to establish a
 * link as if msql_connect was called, and use it.
 * @return resource Returns a positive mSQL query identifier to the query result.
 * @throws MsqlException
 *
 */
function msql_db_query(string $database, string $query, $link_identifier = null)
{
    error_clear_last();
    if ($link_identifier !== null) {
        $result = \msql_db_query($database, $query, $link_identifier);
    } else {
        $result = \msql_db_query($database, $query);
    }
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * msql_drop_db attempts to drop (remove) a database
 * from the mSQL server.
 *
 * @param string $database_name The name of the database.
 * @param resource|null $link_identifier The mSQL connection.
 * If not specified, the last link opened by msql_connect
 * is assumed. If no such link is found, the function will try to establish a
 * link as if msql_connect was called, and use it.
 * @throws MsqlException
 *
 */
function msql_drop_db(string $database_name, $link_identifier = null): void
{
    error_clear_last();
    if ($link_identifier !== null) {
        $result = \msql_drop_db($database_name, $link_identifier);
    } else {
        $result = \msql_drop_db($database_name);
    }
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
}


/**
 * msql_field_len returns the length of the specified
 * field.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * msql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 1.
 * @return int Returns the length of the specified field.
 * @throws MsqlException
 *
 */
function msql_field_len($result, int $field_offset): int
{
    error_clear_last();
    $result = \msql_field_len($result, $field_offset);
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * msql_field_name gets the name of the specified field
 * index.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * msql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 1.
 * @return string The name of the field.
 * @throws MsqlException
 *
 */
function msql_field_name($result, int $field_offset): string
{
    error_clear_last();
    $result = \msql_field_name($result, $field_offset);
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Seeks to the specified field offset. If the next call to
 * msql_fetch_field won't include a field offset, this
 * field would be returned.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * msql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 1.
 * @throws MsqlException
 *
 */
function msql_field_seek($result, int $field_offset): void
{
    error_clear_last();
    $result = \msql_field_seek($result, $field_offset);
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
}


/**
 * Returns the name of the table that the specified field is in.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * msql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 1.
 * @return int The name of the table on success.
 * @throws MsqlException
 *
 */
function msql_field_table($result, int $field_offset): int
{
    error_clear_last();
    $result = \msql_field_table($result, $field_offset);
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * msql_field_type gets the type of the specified field
 * index.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * msql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 1.
 * @return string The type of the field. One of int,
 * char, real, ident,
 * null or unknown. This functions will
 * return FALSE on failure.
 * @throws MsqlException
 *
 */
function msql_field_type($result, int $field_offset): string
{
    error_clear_last();
    $result = \msql_field_type($result, $field_offset);
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * msql_free_result frees the memory associated
 * with query_identifier.  When PHP completes a
 * request, this memory is freed automatically, so you only need to
 * call this function when you want to make sure you don't use too
 * much memory while the script is running.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * msql_query.
 * @throws MsqlException
 *
 */
function msql_free_result($result): void
{
    error_clear_last();
    $result = \msql_free_result($result);
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
}


/**
 * msql_pconnect acts very much like
 * msql_connect with two major differences.
 *
 * First, when connecting, the function would first try to find a
 * (persistent) link that's already open with the same host.
 * If one is found, an identifier for it will be returned instead of opening
 * a new connection.
 *
 * Second, the connection to the SQL server will not be closed when the
 * execution of the script ends.  Instead, the link will remain open for
 * future use (msql_close will not close links
 * established by this function).
 *
 * @param string $hostname The hostname can also include a port number. e.g.
 * hostname,port.
 *
 * If not specified, the connection is established by the means of a Unix
 * domain socket, being more efficient than a localhost TCP socket
 * connection.
 * @return resource Returns a positive mSQL link identifier on success.
 * @throws MsqlException
 *
 */
function msql_pconnect(string $hostname = null)
{
    error_clear_last();
    if ($hostname !== null) {
        $result = \msql_pconnect($hostname);
    } else {
        $result = \msql_pconnect();
    }
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * msql_query sends a query to the currently active
 * database on the server that's associated with the specified link
 * identifier.
 *
 * @param string $query The SQL query.
 * @param resource|null $link_identifier The mSQL connection.
 * If not specified, the last link opened by msql_connect
 * is assumed. If no such link is found, the function will try to establish a
 * link as if msql_connect was called, and use it.
 * @return resource Returns a positive mSQL query identifier on success.
 * @throws MsqlException
 *
 */
function msql_query(string $query, $link_identifier = null)
{
    error_clear_last();
    if ($link_identifier !== null) {
        $result = \msql_query($query, $link_identifier);
    } else {
        $result = \msql_query($query);
    }
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
    return $result;
}


/**
 * msql_select_db sets the current active database on
 * the server that's associated with the specified
 * link_identifier.
 *
 * Subsequent calls to msql_query will be made on the
 * active database.
 *
 * @param string $database_name The database name.
 * @param resource|null $link_identifier The mSQL connection.
 * If not specified, the last link opened by msql_connect
 * is assumed. If no such link is found, the function will try to establish a
 * link as if msql_connect was called, and use it.
 * @throws MsqlException
 *
 */
function msql_select_db(string $database_name, $link_identifier = null): void
{
    error_clear_last();
    if ($link_identifier !== null) {
        $result = \msql_select_db($database_name, $link_identifier);
    } else {
        $result = \msql_select_db($database_name);
    }
    if ($result === false) {
        throw MsqlException::createFromPhpError();
    }
}
