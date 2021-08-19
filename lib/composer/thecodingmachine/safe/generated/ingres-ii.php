<?php

namespace Safe;

use Safe\Exceptions\IngresiiException;

/**
 * ingres_autocommit is called before opening a
 * transaction (before the first call to
 * ingres_query or just after a call to
 * ingres_rollback or
 * ingres_commit) to switch the
 * autocommit mode of the server on or off (when the script begins
 * the autocommit mode is off).
 *
 * When autocommit mode is on, every query is automatically
 * committed by the server, as if ingres_commit
 * was called after every call to ingres_query.
 * To see if autocommit is enabled use,
 * ingres_autocommit_state.
 *
 * By default Ingres will rollback any uncommitted transactions at the end of
 * a request. Use this function or ingres_commit to
 * ensure your data is committed to the database.
 *
 * @param resource $link The connection link identifier
 * @throws IngresiiException
 *
 */
function ingres_autocommit($link): void
{
    error_clear_last();
    $result = \ingres_autocommit($link);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
}


/**
 * ingres_close closes the connection to
 * the Ingres server that is associated with the specified link.
 *
 * ingres_close is usually unnecessary, as it
 * will not close persistent connections and all non-persistent connections
 * are automatically closed at the end of the script.
 *
 * @param resource $link The connection link identifier
 * @throws IngresiiException
 *
 */
function ingres_close($link): void
{
    error_clear_last();
    $result = \ingres_close($link);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
}


/**
 * ingres_commit commits the currently open
 * transaction, making all changes made to the database permanent.
 *
 * This closes the transaction. A new transaction can be opened by sending a
 * query with ingres_query.
 *
 * You can also have the server commit automatically after every
 * query by calling ingres_autocommit before
 * opening the transaction.
 *
 * By default Ingres will roll back any uncommitted transactions at the end of
 * a request. Use this function or ingres_autocommit to
 * ensure your that data is committed to the database.
 *
 * @param resource $link The connection link identifier
 * @throws IngresiiException
 *
 */
function ingres_commit($link): void
{
    error_clear_last();
    $result = \ingres_commit($link);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
}


/**
 * ingres_connect opens a connection with the
 * given Ingres database.
 *
 * The connection is closed when the script ends or when
 * ingres_close is called on this link.
 *
 * @param string $database The database name. Must follow the syntax:
 *
 * [vnode::]dbname[/svr_class]
 * @param string $username The Ingres user name
 * @param string $password The password associated with username
 * @param array $options ingres_connect options
 *
 *
 *
 *
 * Option name
 * Option type
 * Description
 * Example
 *
 *
 *
 *
 * date_century_boundary
 * integer
 * The threshold by which a 2-digit year is determined to be in
 * the current century or in the next century. Equivalent to II_DATE_CENTURY_BOUNDARY.
 * 50
 *
 *
 * group
 * string
 * Specifies the group ID of the user, equivalent to the "-G"
 * flag
 * payroll
 *
 *
 * role
 * string
 * The role ID of the application. If a role password is
 * required, the parameter value should be specified as "role/password"
 *
 *
 * effective_user
 * string
 * The ingres user account being impersonated, equivalent to the "-u" flag
 * another_user
 *
 *
 * dbms_password
 * string
 * The internal database password for the user connecting to Ingres
 * s3cr3t
 *
 *
 * table_structure
 * string
 *
 * The default structure for new tables.
 * Valid values for table_structure are:
 *
 * INGRES_STRUCTURE_BTREE
 * INGRES_STRUCTURE_HASH
 * INGRES_STRUCTURE_HEAP
 * INGRES_STRUCTURE_ISAM
 * INGRES_STRUCTURE_CBTREE
 * INGRES_STRUCTURE_CISAM
 * INGRES_STRUCTURE_CHASH
 * INGRES_STRUCTURE_CHEAP
 *
 *
 *
 * INGRES_STRUCTURE_BTREE
 *
 *
 * index_structure
 * string
 *
 * The default structure for new secondary indexes. Valid values
 * for index_structure are:
 *
 * INGRES_STRUCTURE_CBTREE
 * INGRES_STRUCTURE_CISAM
 * INGRES_STRUCTURE_CHASH
 * INGRES_STRUCTURE_BTREE
 * INGRES_STRUCTURE_HASH
 * INGRES_STRUCTURE_ISAM
 *
 *
 *
 * INGRES_STRUCTURE_HASH
 *
 *
 * login_local
 * boolean
 * Determines how the connection user ID and password are
 * used when a VNODE is included in the target database string.
 * If set to TRUE, the user ID and password are used to locally access
 * the VNODE, and the VNODE login information is used to establish the DBMS
 * connection. If set to FALSE, the process user ID is used to access
 * the VNODE, and the connection user ID and password are used in place
 * of the VNODE login information to establish the DBMS connection.
 * This parameter is ignored if no VNODE is included in the target
 * database string. The default is FALSE.
 * TRUE
 *
 *
 * timezone
 * string
 * Controls the timezone of the session. If not set it will
 * default to the value defined by II_TIMEZONE_NAME. If
 * II_TIMEZONE_NAME is not defined, NA-PACIFIC (GMT-8 with Daylight
 * Savings) is used.
 *
 *
 * date_format
 * integer
 * Sets the allowable input and output format for Ingres dates.
 * Defaults to the value defined by II_DATE_FORMAT. If II_DATE_FORMAT is
 * not set the default date format is US, e.g. mm/dd/yy. Valid values
 * for date_format are:
 *
 * INGRES_DATE_DMY
 * INGRES_DATE_FINISH
 * INGRES_DATE_GERMAN
 * INGRES_DATE_ISO
 * INGRES_DATE_ISO4
 * INGRES_DATE_MDY
 * INGRES_DATE_MULTINATIONAL
 * INGRES_DATE_MULTINATIONAL4
 * INGRES_DATE_YMD
 * INGRES_DATE_US
 *
 *
 *
 * INGRES_DATE_MULTINATIONAL4
 *
 *
 * decimal_separator
 * string
 * The character identifier for decimal data
 * ","
 *
 *
 * money_lort
 * integer
 * Leading or trailing currency sign. Valid values for money_lort
 * are:
 *
 * INGRES_MONEY_LEADING
 * INGRES_MONEY_TRAILING
 *
 *
 *
 * INGRES_MONEY_TRAILING
 *
 *
 * money_sign
 * string
 * The currency symbol to be used with the MONEY datatype
 * €
 *
 *
 * money_precision
 * integer
 * The precision of the MONEY datatype
 * 3
 *
 *
 * float4_precision
 * integer
 * Precision of the FLOAT4 datatype
 * 10
 *
 *
 * float8_precision
 * integer
 * Precision of the FLOAT8 data
 * 10
 *
 *
 * blob_segment_length
 * integer
 * The amount of data in bytes to fetch at a time when retrieving
 * BLOB or CLOB data, defaults to 4096 bytes when not explicitly set
 * 8192
 *
 *
 *
 *
 *
 * The default structure for new tables.
 * Valid values for table_structure are:
 *
 * INGRES_STRUCTURE_BTREE
 * INGRES_STRUCTURE_HASH
 * INGRES_STRUCTURE_HEAP
 * INGRES_STRUCTURE_ISAM
 * INGRES_STRUCTURE_CBTREE
 * INGRES_STRUCTURE_CISAM
 * INGRES_STRUCTURE_CHASH
 * INGRES_STRUCTURE_CHEAP
 *
 *
 * The default structure for new secondary indexes. Valid values
 * for index_structure are:
 *
 * INGRES_STRUCTURE_CBTREE
 * INGRES_STRUCTURE_CISAM
 * INGRES_STRUCTURE_CHASH
 * INGRES_STRUCTURE_BTREE
 * INGRES_STRUCTURE_HASH
 * INGRES_STRUCTURE_ISAM
 *
 *
 * Sets the allowable input and output format for Ingres dates.
 * Defaults to the value defined by II_DATE_FORMAT. If II_DATE_FORMAT is
 * not set the default date format is US, e.g. mm/dd/yy. Valid values
 * for date_format are:
 *
 * INGRES_DATE_DMY
 * INGRES_DATE_FINISH
 * INGRES_DATE_GERMAN
 * INGRES_DATE_ISO
 * INGRES_DATE_ISO4
 * INGRES_DATE_MDY
 * INGRES_DATE_MULTINATIONAL
 * INGRES_DATE_MULTINATIONAL4
 * INGRES_DATE_YMD
 * INGRES_DATE_US
 *
 *
 * Leading or trailing currency sign. Valid values for money_lort
 * are:
 *
 * INGRES_MONEY_LEADING
 * INGRES_MONEY_TRAILING
 *
 * @return resource Returns a Ingres link resource on success
 * @throws IngresiiException
 *
 */
function ingres_connect(string $database = null, string $username = null, string $password = null, array $options = null)
{
    error_clear_last();
    if ($options !== null) {
        $result = \ingres_connect($database, $username, $password, $options);
    } elseif ($password !== null) {
        $result = \ingres_connect($database, $username, $password);
    } elseif ($username !== null) {
        $result = \ingres_connect($database, $username);
    } elseif ($database !== null) {
        $result = \ingres_connect($database);
    } else {
        $result = \ingres_connect();
    }
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
    return $result;
}


/**
 * Execute a query prepared using ingres_prepare.
 *
 * @param resource $result The result query identifier
 * @param array $params An array of parameter values to be used with the query
 * @param string $types A string containing a sequence of types for the parameter values
 * passed. See the types parameter in
 * ingres_query for the list of type codes.
 * @throws IngresiiException
 *
 */
function ingres_execute($result, array $params = null, string $types = null): void
{
    error_clear_last();
    if ($types !== null) {
        $result = \ingres_execute($result, $params, $types);
    } elseif ($params !== null) {
        $result = \ingres_execute($result, $params);
    } else {
        $result = \ingres_execute($result);
    }
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
}


/**
 * ingres_field_name returns the name of a field
 * in a query result.
 *
 * @param resource $result The query result identifier
 * @param int $index index is the field whose name will be
 * retrieved.
 *
 * The possible values of index depend upon
 * the value
 * of ingres.array_index_start.
 * If ingres.array_index_start
 * is 1 (the default)
 * then index must be
 * between 1 and the value returned
 * by ingres_num_fields. If ingres.array_index_start
 * is 0 then index must
 * be between 0
 * and ingres_num_fields -
 * 1.
 * @return string Returns the name of a field
 * in a query result
 * @throws IngresiiException
 *
 */
function ingres_field_name($result, int $index): string
{
    error_clear_last();
    $result = \ingres_field_name($result, $index);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
    return $result;
}


/**
 * Get the type of a field in a query result.
 *
 * @param resource $result The query result identifier
 * @param int $index index is the field whose type will be
 * retrieved.
 *
 * The possible values of index depend upon
 * the value
 * of ingres.array_index_start.
 * If ingres.array_index_start
 * is 1 (the default)
 * then index must be
 * between 1 and the value returned
 * by ingres_num_fields. If ingres.array_index_start
 * is 0 then index must
 * be between 0
 * and ingres_num_fields -
 * 1.
 * @return string ingres_field_type returns the type of a
 * field in a query result.  Examples of
 * types returned are IIAPI_BYTE_TYPE,
 * IIAPI_CHA_TYPE, IIAPI_DTE_TYPE,
 * IIAPI_FLT_TYPE, IIAPI_INT_TYPE,
 * IIAPI_VCH_TYPE. Some of these types can map to more
 * than one SQL type depending on the length of the field (see
 * ingres_field_length). For example
 * IIAPI_FLT_TYPE can be a float4 or a float8. For detailed
 * information, see the Ingres OpenAPI User Guide, Appendix
 * "Data Types" in the Ingres documentation.
 * @throws IngresiiException
 *
 */
function ingres_field_type($result, int $index): string
{
    error_clear_last();
    $result = \ingres_field_type($result, $index);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param resource $result The query result identifier
 * @throws IngresiiException
 *
 */
function ingres_free_result($result): void
{
    error_clear_last();
    $result = \ingres_free_result($result);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
}


/**
 * Open a persistent connection to an Ingres database.
 *
 * There are only two differences between this function and
 * ingres_connect: First, when connecting, the
 * function will initially try to find a (persistent) link that is
 * already opened with the same parameters.  If one is found, an
 * identifier for it will be returned instead of opening a new
 * connection. Second, the connection to the Ingres server will not
 * be closed when the execution of the script ends.  Instead, the
 * link will remain open for future use
 * (ingres_close will not close links
 * established by ingres_pconnect). This type
 * of link is therefore called "persistent".
 *
 * @param string $database The database name. Must follow the syntax:
 *
 * [vnode::]dbname[/svr_class]
 * @param string $username The Ingres user name
 * @param string $password The password associated with username
 * @param array $options See ingres_connect for the list of options that
 * can be passed
 * @return resource Returns an Ingres link resource on success
 * @throws IngresiiException
 *
 */
function ingres_pconnect(string $database = null, string $username = null, string $password = null, array $options = null)
{
    error_clear_last();
    if ($options !== null) {
        $result = \ingres_pconnect($database, $username, $password, $options);
    } elseif ($password !== null) {
        $result = \ingres_pconnect($database, $username, $password);
    } elseif ($username !== null) {
        $result = \ingres_pconnect($database, $username);
    } elseif ($database !== null) {
        $result = \ingres_pconnect($database);
    } else {
        $result = \ingres_pconnect();
    }
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
    return $result;
}


/**
 * This function is used to position the cursor associated with the result
 * resource before issuing a fetch.  If ingres.array_index_start
 * is set to 0 then the first row is 0 else it is 1.
 * ingres_result_seek can be used only with queries that
 * make use of scrollable
 * cursors. It cannot be used with
 * ingres_unbuffered_query.
 *
 * @param resource $result The result identifier for a query
 * @param int $position The row to position the cursor on. If ingres.array_index_start
 * is set to 0, then the first row is 0, else it is 1
 * @throws IngresiiException
 *
 */
function ingres_result_seek($result, int $position): void
{
    error_clear_last();
    $result = \ingres_result_seek($result, $position);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
}


/**
 * ingres_rollback rolls back the currently
 * open transaction, actually cancelling all changes made to the
 * database during the transaction.
 *
 * This closes the transaction. A new transaction can be opened by sending a
 * query with ingres_query.
 *
 * @param resource $link The connection link identifier
 * @throws IngresiiException
 *
 */
function ingres_rollback($link): void
{
    error_clear_last();
    $result = \ingres_rollback($link);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
}


/**
 * ingres_set_environment is called to set environmental
 * options that affect the output of certain values from Ingres, such as the
 * timezone, date format, decimal character separator, and float precision.
 *
 * @param resource $link The connection link identifier
 * @param array $options An enumerated array of option name/value pairs. The following table
 * lists the option name and the expected type
 *
 *
 *
 *
 *
 * Option name
 * Option type
 * Description
 * Example
 *
 *
 *
 *
 * date_century_boundary
 * integer
 * The threshold by which a 2-digit year is determined to be in
 * the current century or in the next century. Equivalent to II_DATE_CENTURY_BOUNDARY
 * 50
 *
 *
 * timezone
 * string
 * Controls the timezone of the session. If not set, it will
 * default the value defined by II_TIMEZONE_NAME. If
 * II_TIMEZONE_NAME is not defined, NA-PACIFIC (GMT-8 with Daylight
 * Savings) is used.
 * UNITED-KINGDOM
 *
 *
 * date_format
 * integer
 * Sets the allowable input and output format for Ingres dates.
 * Defaults to the value defined by II_DATE_FORMAT. If II_DATE_FORMAT is
 * not set, the default date format is US, for example mm/dd/yy. Valid values
 * for date_format are:
 *
 * INGRES_DATE_DMY
 * INGRES_DATE_FINISH
 * INGRES_DATE_GERMAN
 * INGRES_DATE_ISO
 * INGRES_DATE_ISO4
 * INGRES_DATE_MDY
 * INGRES_DATE_MULTINATIONAL
 * INGRES_DATE_MULTINATIONAL4
 * INGRES_DATE_YMD
 * INGRES_DATE_US
 *
 *
 *
 * INGRES_DATE_ISO4
 *
 *
 * decimal_separator
 * string
 * The character identifier for decimal data
 * ","
 *
 *
 * money_lort
 * integer
 * Leading or trailing currency sign. Valid values for money_lort
 * are:
 *
 * INGRES_MONEY_LEADING
 * INGRES_MONEY_TRAILING
 *
 *
 *
 * INGRES_MONEY_LEADING
 *
 *
 * money_sign
 * string
 * The currency symbol to be used with the MONEY datatype
 * €
 *
 *
 * money_precision
 * integer
 * The precision of the MONEY datatype
 * 2
 *
 *
 * float4_precision
 * integer
 * Precision of the FLOAT4 datatype
 * 10
 *
 *
 * float8_precision
 * integer
 * Precision of the FLOAT8 data
 * 10
 *
 *
 * blob_segment_length
 * integer
 * The amount of data in bytes to fetch at a time when retrieving
 * BLOB or CLOB data. Defaults to 4096 bytes when not set explicitly
 * 8192
 *
 *
 *
 *
 *
 * Sets the allowable input and output format for Ingres dates.
 * Defaults to the value defined by II_DATE_FORMAT. If II_DATE_FORMAT is
 * not set, the default date format is US, for example mm/dd/yy. Valid values
 * for date_format are:
 *
 * INGRES_DATE_DMY
 * INGRES_DATE_FINISH
 * INGRES_DATE_GERMAN
 * INGRES_DATE_ISO
 * INGRES_DATE_ISO4
 * INGRES_DATE_MDY
 * INGRES_DATE_MULTINATIONAL
 * INGRES_DATE_MULTINATIONAL4
 * INGRES_DATE_YMD
 * INGRES_DATE_US
 *
 *
 * Leading or trailing currency sign. Valid values for money_lort
 * are:
 *
 * INGRES_MONEY_LEADING
 * INGRES_MONEY_TRAILING
 *
 * @throws IngresiiException
 *
 */
function ingres_set_environment($link, array $options): void
{
    error_clear_last();
    $result = \ingres_set_environment($link, $options);
    if ($result === false) {
        throw IngresiiException::createFromPhpError();
    }
}
