<?php

namespace Safe;

use Safe\Exceptions\SqlsrvException;

/**
 * The transaction begun by sqlsrv_begin_transaction includes
 * all statements that were executed after the call to
 * sqlsrv_begin_transaction and before calls to
 * sqlsrv_rollback or sqlsrv_commit.
 * Explicit transactions should be started and committed or rolled back using
 * these functions instead of executing SQL statements that begin and commit/roll
 * back transactions. For more information, see
 * SQLSRV Transactions.
 *
 * @param resource $conn The connection resource returned by a call to sqlsrv_connect.
 * @throws SqlsrvException
 *
 */
function sqlsrv_begin_transaction($conn): void
{
    error_clear_last();
    $result = \sqlsrv_begin_transaction($conn);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
}


/**
 * Cancels a statement. Any results associated with the statement that have not
 * been consumed are deleted. After sqlsrv_cancel has been
 * called, the specified statement can be re-executed if it was created with
 * sqlsrv_prepare. Calling sqlsrv_cancel
 * is not necessary if all the results associated with the statement have been
 * consumed.
 *
 * @param resource $stmt The statement resource to be cancelled.
 * @throws SqlsrvException
 *
 */
function sqlsrv_cancel($stmt): void
{
    error_clear_last();
    $result = \sqlsrv_cancel($stmt);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
}


/**
 * Returns information about the client and specified connection
 *
 * @param resource $conn The connection about which information is returned.
 * @return array Returns an associative array with keys described in the table below.
 *
 * Array returned by sqlsrv_client_info
 *
 *
 *
 * Key
 * Description
 *
 *
 *
 *
 * DriverDllName
 * SQLNCLI10.DLL
 *
 *
 * DriverODBCVer
 * ODBC version (xx.yy)
 *
 *
 * DriverVer
 * SQL Server Native Client DLL version (10.5.xxx)
 *
 *
 * ExtensionVer
 * php_sqlsrv.dll version (2.0.xxx.x)
 *
 *
 *
 *
 * @throws SqlsrvException
 *
 */
function sqlsrv_client_info($conn): array
{
    error_clear_last();
    $result = \sqlsrv_client_info($conn);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
    return $result;
}


/**
 * Closes an open connection and releases resourses associated with the connection.
 *
 * @param resource $conn The connection to be closed.
 * @throws SqlsrvException
 *
 */
function sqlsrv_close($conn): void
{
    error_clear_last();
    $result = \sqlsrv_close($conn);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
}


/**
 * Commits a transaction that was begun with sqlsrv_begin_transaction.
 * The connection is returned to auto-commit mode after sqlsrv_commit
 * is called. The transaction that is committed includes all statements that were
 * executed after the call to sqlsrv_begin_transaction.
 * Explicit transactions should be started and committed or rolled back using these
 * functions instead of executing SQL statements that begin and commit/roll back
 * transactions. For more information, see
 * SQLSRV Transactions.
 *
 * @param resource $conn The connection on which the transaction is to be committed.
 * @throws SqlsrvException
 *
 */
function sqlsrv_commit($conn): void
{
    error_clear_last();
    $result = \sqlsrv_commit($conn);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
}


/**
 * Changes the driver error handling and logging configurations.
 *
 * @param string $setting The name of the setting to set. The possible values are
 * "WarningsReturnAsErrors", "LogSubsystems", and "LogSeverity".
 * @param mixed $value The value of the specified setting. The following table shows possible values:
 *
 * Error and Logging Setting Options
 *
 *
 *
 * Setting
 * Options
 *
 *
 *
 *
 * WarningsReturnAsErrors
 * 1 (TRUE) or 0 (FALSE)
 *
 *
 * LogSubsystems
 * SQLSRV_LOG_SYSTEM_ALL (-1)
 * SQLSRV_LOG_SYSTEM_CONN (2)
 * SQLSRV_LOG_SYSTEM_INIT (1)
 * SQLSRV_LOG_SYSTEM_OFF (0)
 * SQLSRV_LOG_SYSTEM_STMT (4)
 * SQLSRV_LOG_SYSTEM_UTIL (8)
 *
 *
 * LogSeverity
 * SQLSRV_LOG_SEVERITY_ALL (-1)
 * SQLSRV_LOG_SEVERITY_ERROR (1)
 * SQLSRV_LOG_SEVERITY_NOTICE (4)
 * SQLSRV_LOG_SEVERITY_WARNING (2)
 *
 *
 *
 *
 * @throws SqlsrvException
 *
 */
function sqlsrv_configure(string $setting, $value): void
{
    error_clear_last();
    $result = \sqlsrv_configure($setting, $value);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
}


/**
 * Executes a statement prepared with sqlsrv_prepare. This
 * function is ideal for executing a prepared statement multiple times with
 * different parameter values.
 *
 * @param resource $stmt A statement resource returned by sqlsrv_prepare.
 * @throws SqlsrvException
 *
 */
function sqlsrv_execute($stmt): void
{
    error_clear_last();
    $result = \sqlsrv_execute($stmt);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
}


/**
 * Frees all resources for the specified statement. The statement cannot be used
 * after sqlsrv_free_stmt has been called on it. If
 * sqlsrv_free_stmt is called on an in-progress statement
 * that alters server state, statement execution is terminated and the statement
 * is rolled back.
 *
 * @param resource $stmt The statement for which resources are freed.
 * Note that NULL is a valid parameter value. This allows the function to be
 * called multiple times in a script.
 * @throws SqlsrvException
 *
 */
function sqlsrv_free_stmt($stmt): void
{
    error_clear_last();
    $result = \sqlsrv_free_stmt($stmt);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
}


/**
 * Gets field data from the currently selected row. Fields must be accessed in
 * order. Field indices start at 0.
 *
 * @param resource $stmt A statement resource returned by sqlsrv_query or
 * sqlsrv_execute.
 * @param int $fieldIndex The index of the field to be retrieved. Field indices start at 0. Fields
 * must be accessed in order. i.e. If you access field index 1, then field
 * index 0 will not be available.
 * @param int $getAsType The PHP data type for the returned field data. If this parameter is not
 * set, the field data will be returned as its default PHP data type.
 * For information about default PHP data types, see
 * Default PHP Data Types
 * in the Microsoft SQLSRV documentation.
 * @return mixed Returns data from the specified field on success.
 * @throws SqlsrvException
 *
 */
function sqlsrv_get_field($stmt, int $fieldIndex, int $getAsType = null)
{
    error_clear_last();
    if ($getAsType !== null) {
        $result = \sqlsrv_get_field($stmt, $fieldIndex, $getAsType);
    } else {
        $result = \sqlsrv_get_field($stmt, $fieldIndex);
    }
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
    return $result;
}


/**
 * Makes the next result of the specified statement active. Results include result
 * sets, row counts, and output parameters.
 *
 * @param resource $stmt The statement on which the next result is being called.
 * @return bool|null Returns TRUE if the next result was successfully retrieved, FALSE if an error
 * occurred, and NULL if there are no more results to retrieve.
 * @throws SqlsrvException
 *
 */
function sqlsrv_next_result($stmt): ?bool
{
    error_clear_last();
    $result = \sqlsrv_next_result($stmt);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the number of fields (columns) on a statement.
 *
 * @param resource $stmt The statement for which the number of fields is returned.
 * sqlsrv_num_fields can be called on a statement before
 * or after statement execution.
 * @return int Returns the number of fields on success.
 * @throws SqlsrvException
 *
 */
function sqlsrv_num_fields($stmt): int
{
    error_clear_last();
    $result = \sqlsrv_num_fields($stmt);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
    return $result;
}


/**
 * Retrieves the number of rows in a result set. This function requires that the
 * statement resource be created with a static or keyset cursor. For more information,
 * see sqlsrv_query, sqlsrv_prepare,
 * or Specifying a Cursor Type and Selecting Rows
 * in the Microsoft SQLSRV documentation.
 *
 * @param resource $stmt The statement for which the row count is returned. The statement resource
 * must be created with a static or keyset cursor. For more information, see
 * sqlsrv_query, sqlsrv_prepare, or
 * Specifying a Cursor Type and Selecting Rows
 * in the Microsoft SQLSRV documentation.
 * @return int Returns the number of rows retrieved on success.
 * If a forward cursor (the default) or dynamic cursor is used, FALSE is returned.
 * @throws SqlsrvException
 *
 */
function sqlsrv_num_rows($stmt): int
{
    error_clear_last();
    $result = \sqlsrv_num_rows($stmt);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
    return $result;
}


/**
 * Prepares a query for execution. This function is ideal for preparing a query
 * that will be executed multiple times with different parameter values.
 *
 * @param resource $conn A connection resource returned by sqlsrv_connect.
 * @param string $sql The string that defines the query to be prepared and executed.
 * @param array $params An array specifying parameter information when executing a parameterized
 * query. Array elements can be any of the following:
 *
 * A literal value
 * A PHP variable
 * An array with this structure:
 * array($value [, $direction [, $phpType [, $sqlType]]])
 *
 * The following table describes the elements in the array structure above:
 * @param array $options An array specifying query property options. The supported keys are described
 * in the following table:
 * @return resource Returns a statement resource on success.
 * @throws SqlsrvException
 *
 */
function sqlsrv_prepare($conn, string $sql, array $params = null, array $options = null)
{
    error_clear_last();
    if ($options !== null) {
        $result = \sqlsrv_prepare($conn, $sql, $params, $options);
    } elseif ($params !== null) {
        $result = \sqlsrv_prepare($conn, $sql, $params);
    } else {
        $result = \sqlsrv_prepare($conn, $sql);
    }
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
    return $result;
}


/**
 * Prepares and executes a query.
 *
 * @param resource $conn A connection resource returned by sqlsrv_connect.
 * @param string $sql The string that defines the query to be prepared and executed.
 * @param array $params An array specifying parameter information when executing a parameterized query.
 * Array elements can be any of the following:
 *
 * A literal value
 * A PHP variable
 * An array with this structure:
 * array($value [, $direction [, $phpType [, $sqlType]]])
 *
 * The following table describes the elements in the array structure above:
 * @param array $options An array specifying query property options. The supported keys are described
 * in the following table:
 * @return resource Returns a statement resource on success.
 * @throws SqlsrvException
 *
 */
function sqlsrv_query($conn, string $sql, array $params = null, array $options = null)
{
    error_clear_last();
    if ($options !== null) {
        $result = \sqlsrv_query($conn, $sql, $params, $options);
    } elseif ($params !== null) {
        $result = \sqlsrv_query($conn, $sql, $params);
    } else {
        $result = \sqlsrv_query($conn, $sql);
    }
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
    return $result;
}


/**
 * Rolls back a transaction that was begun with sqlsrv_begin_transaction
 * and returns the connection to auto-commit mode.
 *
 * @param resource $conn The connection resource returned by a call to sqlsrv_connect.
 * @throws SqlsrvException
 *
 */
function sqlsrv_rollback($conn): void
{
    error_clear_last();
    $result = \sqlsrv_rollback($conn);
    if ($result === false) {
        throw SqlsrvException::createFromPhpError();
    }
}
