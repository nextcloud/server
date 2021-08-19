<?php

namespace Safe;

use Safe\Exceptions\MysqlException;

/**
 * mysql_close closes the non-persistent connection to
 * the MySQL server that's associated with the specified link identifier. If
 * link_identifier isn't specified, the last opened
 * link is used.
 *
 *
 * Open non-persistent MySQL connections and result sets are automatically destroyed when a
 * PHP script finishes its execution. So, while explicitly closing open
 * connections and freeing result sets is optional, doing so is recommended.
 * This will immediately return resources to PHP and MySQL, which can
 * improve performance. For related information, see
 * freeing resources
 *
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no connection is found or
 * established, an E_WARNING level error is
 * generated.
 * @throws MysqlException
 *
 */
function mysql_close($link_identifier = null): void
{
    error_clear_last();
    $result = \mysql_close($link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
}


/**
 * Opens or reuses a connection to a MySQL server.
 *
 * @param string $server The MySQL server. It can also include a port number. e.g.
 * "hostname:port" or a path to a local socket e.g. ":/path/to/socket" for
 * the localhost.
 *
 * If the PHP directive
 * mysql.default_host is undefined (default), then the default
 * value is 'localhost:3306'. In SQL safe mode, this parameter is ignored
 * and value 'localhost:3306' is always used.
 * @param string $username The username. Default value is defined by mysql.default_user. In
 * SQL safe mode, this parameter is ignored and the name of the user that
 * owns the server process is used.
 * @param string $password The password. Default value is defined by mysql.default_password. In
 * SQL safe mode, this parameter is ignored and empty password is used.
 * @param bool $new_link If a second call is made to mysql_connect
 * with the same arguments, no new link will be established, but
 * instead, the link identifier of the already opened link will be
 * returned. The new_link parameter modifies this
 * behavior and makes mysql_connect always open
 * a new link, even if mysql_connect was called
 * before with the same parameters.
 * In SQL safe mode, this parameter is ignored.
 * @param int $client_flags The client_flags parameter can be a combination
 * of the following constants:
 * 128 (enable LOAD DATA LOCAL handling),
 * MYSQL_CLIENT_SSL,
 * MYSQL_CLIENT_COMPRESS,
 * MYSQL_CLIENT_IGNORE_SPACE or
 * MYSQL_CLIENT_INTERACTIVE.
 * Read the section about  for further information.
 * In SQL safe mode, this parameter is ignored.
 * @return resource Returns a MySQL link identifier on success.
 * @throws MysqlException
 *
 */
function mysql_connect(string $server = null, string $username = null, string $password = null, bool $new_link = false, int $client_flags = 0)
{
    error_clear_last();
    if ($client_flags !== 0) {
        $result = \mysql_connect($server, $username, $password, $new_link, $client_flags);
    } elseif ($new_link !== false) {
        $result = \mysql_connect($server, $username, $password, $new_link);
    } elseif ($password !== null) {
        $result = \mysql_connect($server, $username, $password);
    } elseif ($username !== null) {
        $result = \mysql_connect($server, $username);
    } elseif ($server !== null) {
        $result = \mysql_connect($server);
    } else {
        $result = \mysql_connect();
    }
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * mysql_create_db attempts to create a new
 * database on the server associated with the specified link
 * identifier.
 *
 * @param string $database_name The name of the database being created.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @throws MysqlException
 *
 */
function mysql_create_db(string $database_name, $link_identifier = null): void
{
    error_clear_last();
    $result = \mysql_create_db($database_name, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
}


/**
 * mysql_data_seek moves the internal row
 * pointer of the MySQL result associated with the specified result
 * identifier to point to the specified row number.  The next call
 * to a MySQL fetch function, such as mysql_fetch_assoc,
 * would return that row.
 *
 * row_number starts at 0. The
 * row_number should be a value in the range from 0 to
 * mysql_num_rows - 1. However if the result set
 * is empty (mysql_num_rows == 0), a seek to 0 will
 * fail with an E_WARNING and
 * mysql_data_seek will return FALSE.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @param int $row_number The desired row number of the new result pointer.
 * @throws MysqlException
 *
 */
function mysql_data_seek($result, int $row_number): void
{
    error_clear_last();
    $result = \mysql_data_seek($result, $row_number);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
}


/**
 * Retrieve the database name from a call to
 * mysql_list_dbs.
 *
 * @param resource $result The result pointer from a call to mysql_list_dbs.
 * @param int $row The index into the result set.
 * @param mixed $field The field name.
 * @return string Returns the database name on success. If FALSE
 * is returned, use mysql_error to determine the nature
 * of the error.
 * @throws MysqlException
 *
 */
function mysql_db_name($result, int $row, $field = null): string
{
    error_clear_last();
    $result = \mysql_db_name($result, $row, $field);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * mysql_db_query selects a database, and executes a
 * query on it.
 *
 * @param string $database The name of the database that will be selected.
 * @param string $query The MySQL query.
 *
 * Data inside the query should be properly escaped.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return resource|bool Returns a positive MySQL result resource to the query result. The function also returns TRUE/FALSE for
 * INSERT/UPDATE/DELETE
 * queries to indicate success/failure.
 * @throws MysqlException
 *
 */
function mysql_db_query(string $database, string $query, $link_identifier = null)
{
    error_clear_last();
    $result = \mysql_db_query($database, $query, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * mysql_drop_db attempts to drop (remove) an
 * entire database from the server associated with the specified
 * link identifier. This function is deprecated, it is preferable to use
 * mysql_query to issue an sql
 * DROP DATABASE statement instead.
 *
 * @param string $database_name The name of the database that will be deleted.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @throws MysqlException
 *
 */
function mysql_drop_db(string $database_name, $link_identifier = null): void
{
    error_clear_last();
    $result = \mysql_drop_db($database_name, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
}


/**
 * Returns an array that corresponds to the lengths of each field
 * in the last row fetched by MySQL.
 *
 * mysql_fetch_lengths stores the lengths of
 * each result column in the last row returned by
 * mysql_fetch_row,
 * mysql_fetch_assoc,
 * mysql_fetch_array, and
 * mysql_fetch_object in an array, starting at
 * offset 0.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @return array An array of lengths on success.
 * @throws MysqlException
 *
 */
function mysql_fetch_lengths($result): array
{
    error_clear_last();
    $result = \mysql_fetch_lengths($result);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * mysql_field_flags returns the field flags of
 * the specified field. The flags are reported as a single word
 * per flag separated by a single space, so that you can split the
 * returned value using explode.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 0. If
 * field_offset does not exist, an error of level
 * E_WARNING is also issued.
 * @return string Returns a string of flags associated with the result.
 *
 * The following flags are reported, if your version of MySQL
 * is current enough to support them: "not_null",
 * "primary_key", "unique_key",
 * "multiple_key", "blob",
 * "unsigned", "zerofill",
 * "binary", "enum",
 * "auto_increment" and "timestamp".
 * @throws MysqlException
 *
 */
function mysql_field_flags($result, int $field_offset): string
{
    error_clear_last();
    $result = \mysql_field_flags($result, $field_offset);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * mysql_field_len returns the length of the
 * specified field.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 0. If
 * field_offset does not exist, an error of level
 * E_WARNING is also issued.
 * @return int The length of the specified field index on success.
 * @throws MysqlException
 *
 */
function mysql_field_len($result, int $field_offset): int
{
    error_clear_last();
    $result = \mysql_field_len($result, $field_offset);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * mysql_field_name returns the name of the
 * specified field index.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 0. If
 * field_offset does not exist, an error of level
 * E_WARNING is also issued.
 * @return string The name of the specified field index on success.
 * @throws MysqlException
 *
 */
function mysql_field_name($result, int $field_offset): string
{
    error_clear_last();
    $result = \mysql_field_name($result, $field_offset);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Seeks to the specified field offset.  If the next call to
 * mysql_fetch_field doesn't include a field
 * offset, the field offset specified in
 * mysql_field_seek will be returned.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @param int $field_offset The numerical field offset. The
 * field_offset starts at 0. If
 * field_offset does not exist, an error of level
 * E_WARNING is also issued.
 * @throws MysqlException
 *
 */
function mysql_field_seek($result, int $field_offset): void
{
    error_clear_last();
    $result = \mysql_field_seek($result, $field_offset);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
}


/**
 * mysql_free_result will free all memory
 * associated with the result identifier result.
 *
 * mysql_free_result only needs to be called if
 * you are concerned about how much memory is being used for queries
 * that return large result sets.  All associated result memory is
 * automatically freed at the end of the script's execution.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @throws MysqlException
 *
 */
function mysql_free_result($result): void
{
    error_clear_last();
    $result = \mysql_free_result($result);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
}


/**
 * Describes the type of connection in use for the connection, including the
 * server host name.
 *
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return string Returns a string describing the type of MySQL connection in use for the
 * connection.
 * @throws MysqlException
 *
 */
function mysql_get_host_info($link_identifier = null): string
{
    error_clear_last();
    $result = \mysql_get_host_info($link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the MySQL protocol.
 *
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return int Returns the MySQL protocol on success.
 * @throws MysqlException
 *
 */
function mysql_get_proto_info($link_identifier = null): int
{
    error_clear_last();
    $result = \mysql_get_proto_info($link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the MySQL server version.
 *
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return string Returns the MySQL server version on success.
 * @throws MysqlException
 *
 */
function mysql_get_server_info($link_identifier = null): string
{
    error_clear_last();
    $result = \mysql_get_server_info($link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns detailed information about the last query.
 *
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return string Returns information about the statement on success. See the example below for which statements provide information,
 * and what the returned value may look like. Statements that are not listed
 * will return FALSE.
 * @throws MysqlException
 *
 */
function mysql_info($link_identifier = null): string
{
    error_clear_last();
    $result = \mysql_info($link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns a result pointer containing the databases available from the
 * current mysql daemon.
 *
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return resource Returns a result pointer resource on success. Use the mysql_tablename function to traverse
 * this result pointer, or any function for result tables, such as
 * mysql_fetch_array.
 * @throws MysqlException
 *
 */
function mysql_list_dbs($link_identifier = null)
{
    error_clear_last();
    $result = \mysql_list_dbs($link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves information about the given table name.
 *
 * This function is deprecated. It is preferable to use
 * mysql_query to issue an SQL SHOW COLUMNS FROM
 * table [LIKE 'name'] statement instead.
 *
 * @param string $database_name The name of the database that's being queried.
 * @param string $table_name The name of the table that's being queried.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return resource A result pointer resource on success.
 *
 * The returned result can be used with mysql_field_flags,
 * mysql_field_len,
 * mysql_field_name and
 * mysql_field_type.
 * @throws MysqlException
 *
 */
function mysql_list_fields(string $database_name, string $table_name, $link_identifier = null)
{
    error_clear_last();
    $result = \mysql_list_fields($database_name, $table_name, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the current MySQL server threads.
 *
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return resource A result pointer resource on success.
 * @throws MysqlException
 *
 */
function mysql_list_processes($link_identifier = null)
{
    error_clear_last();
    $result = \mysql_list_processes($link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves a list of table names from a MySQL database.
 *
 * This function is deprecated. It is preferable to use
 * mysql_query to issue an SQL SHOW TABLES
 * [FROM db_name] [LIKE 'pattern'] statement instead.
 *
 * @param string $database The name of the database
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return resource A result pointer resource on success.
 *
 * Use the mysql_tablename function to
 * traverse this result pointer, or any function for result tables,
 * such as mysql_fetch_array.
 * @throws MysqlException
 *
 */
function mysql_list_tables(string $database, $link_identifier = null)
{
    error_clear_last();
    $result = \mysql_list_tables($database, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the number of fields from a query.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @return int Returns the number of fields in the result set resource on
 * success.
 * @throws MysqlException
 *
 */
function mysql_num_fields($result): int
{
    error_clear_last();
    $result = \mysql_num_fields($result);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the number of rows from a result set. This command is only valid
 * for statements like SELECT or SHOW that return an actual result set.
 * To retrieve the number of rows affected by a INSERT, UPDATE, REPLACE or
 * DELETE query, use mysql_affected_rows.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @return int The number of rows in a result set on success.
 * @throws MysqlException
 *
 */
function mysql_num_rows($result): int
{
    error_clear_last();
    $result = \mysql_num_rows($result);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * mysql_query sends a unique query (multiple queries
 * are not supported) to the currently
 * active database on the server that's associated with the
 * specified link_identifier.
 *
 * @param string $query An SQL query
 *
 * The query string should not end with a semicolon.
 * Data inside the query should be properly escaped.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return resource|bool For SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset,
 * mysql_query
 * returns a resource on success.
 *
 * For other type of SQL statements, INSERT, UPDATE, DELETE, DROP, etc,
 * mysql_query returns TRUE on success.
 *
 * The returned result resource should be passed to
 * mysql_fetch_array, and other
 * functions for dealing with result tables, to access the returned data.
 *
 * Use mysql_num_rows to find out how many rows
 * were returned for a SELECT statement or
 * mysql_affected_rows to find out how many
 * rows were affected by a DELETE, INSERT, REPLACE, or UPDATE
 * statement.
 *
 * mysql_query will also fail and return FALSE
 * if the user does not have permission to access the table(s) referenced by
 * the query.
 * @throws MysqlException
 *
 */
function mysql_query(string $query, $link_identifier = null)
{
    error_clear_last();
    $result = \mysql_query($query, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Escapes special characters in the unescaped_string,
 * taking into account the current character set of the connection so that it
 * is safe to place it in a mysql_query. If binary data
 * is to be inserted, this function must be used.
 *
 * mysql_real_escape_string calls MySQL's library function
 * mysql_real_escape_string, which prepends backslashes to the following characters:
 * \x00, \n,
 * \r, \, ',
 * " and \x1a.
 *
 * This function must always (with few exceptions) be used to make data
 * safe before sending a query to MySQL.
 *
 * @param string $unescaped_string The string that is to be escaped.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return string Returns the escaped string.
 * @throws MysqlException
 *
 */
function mysql_real_escape_string(string $unescaped_string, $link_identifier = null): string
{
    error_clear_last();
    $result = \mysql_real_escape_string($unescaped_string, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the contents of one cell from a MySQL result set.
 *
 * When working on large result sets, you should consider using one
 * of the functions that fetch an entire row (specified below).  As
 * these functions return the contents of multiple cells in one
 * function call, they're MUCH quicker than
 * mysql_result.  Also, note that specifying a
 * numeric offset for the field argument is much quicker than
 * specifying a fieldname or tablename.fieldname argument.
 *
 * @param resource $result The result resource that
 * is being evaluated. This result comes from a call to
 * mysql_query.
 * @param int $row The row number from the result that's being retrieved. Row numbers
 * start at 0.
 * @param mixed $field The name or offset of the field being retrieved.
 *
 * It can be the field's offset, the field's name, or the field's table
 * dot field name (tablename.fieldname). If the column name has been
 * aliased ('select foo as bar from...'), use the alias instead of the
 * column name. If undefined, the first field is retrieved.
 * @return string The contents of one cell from a MySQL result set on success.
 * @throws MysqlException
 *
 */
function mysql_result($result, int $row, $field = 0): string
{
    error_clear_last();
    $result = \mysql_result($result, $row, $field);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets the current active database on the server that's associated with the
 * specified link identifier. Every subsequent call to
 * mysql_query will be made on the active database.
 *
 * @param string $database_name The name of the database that is to be selected.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @throws MysqlException
 *
 */
function mysql_select_db(string $database_name, $link_identifier = null): void
{
    error_clear_last();
    $result = \mysql_select_db($database_name, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
}


/**
 * Sets the default character set for the current connection.
 *
 * @param string $charset A valid character set name.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @throws MysqlException
 *
 */
function mysql_set_charset(string $charset, $link_identifier = null): void
{
    error_clear_last();
    $result = \mysql_set_charset($charset, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
}


/**
 * Retrieves the table name from a result.
 *
 * This function is deprecated. It is preferable to use
 * mysql_query to issue an SQL SHOW TABLES
 * [FROM db_name] [LIKE 'pattern'] statement instead.
 *
 * @param resource $result A result pointer resource that's returned from
 * mysql_list_tables.
 * @param int $i The integer index (row/table number)
 * @return string The name of the table on success.
 *
 * Use the mysql_tablename function to
 * traverse this result pointer, or any function for result tables,
 * such as mysql_fetch_array.
 * @throws MysqlException
 *
 */
function mysql_tablename($result, int $i): string
{
    error_clear_last();
    $result = \mysql_tablename($result, $i);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the current thread ID. If the connection is lost, and a reconnect
 * with mysql_ping is executed, the thread ID will
 * change. This means only retrieve the thread ID when needed.
 *
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return int The thread ID on success.
 * @throws MysqlException
 *
 */
function mysql_thread_id($link_identifier = null): int
{
    error_clear_last();
    $result = \mysql_thread_id($link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}


/**
 * mysql_unbuffered_query sends the SQL query
 * query to MySQL without automatically
 * fetching and buffering the result rows as
 * mysql_query does.  This saves a considerable
 * amount of memory with SQL queries that produce large result sets,
 * and you can start working on the result set immediately after the
 * first row has been retrieved as you don't have to wait until the
 * complete SQL query has been performed.  To use
 * mysql_unbuffered_query while multiple database
 * connections are open, you must specify the optional parameter
 * link_identifier to identify which connection
 * you want to use.
 *
 * @param string $query The SQL query to execute.
 *
 * Data inside the query should be properly escaped.
 * @param resource $link_identifier The MySQL connection. If the
 * link identifier is not specified, the last link opened by
 * mysql_connect is assumed. If no such link is found, it
 * will try to create one as if mysql_connect had been called
 * with no arguments. If no connection is found or established, an
 * E_WARNING level error is generated.
 * @return resource|bool For SELECT, SHOW, DESCRIBE or EXPLAIN statements,
 * mysql_unbuffered_query
 * returns a resource on success.
 *
 * For other type of SQL statements, UPDATE, DELETE, DROP, etc,
 * mysql_unbuffered_query returns TRUE on success.
 * @throws MysqlException
 *
 */
function mysql_unbuffered_query(string $query, $link_identifier = null)
{
    error_clear_last();
    $result = \mysql_unbuffered_query($query, $link_identifier);
    if ($result === false) {
        throw MysqlException::createFromPhpError();
    }
    return $result;
}
