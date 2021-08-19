<?php

namespace Safe;

use Safe\Exceptions\CubridException;

/**
 * This function frees the memory occupied by the result data. It returns
 * TRUE on success. Note that it can only frees the
 * client fetch buffer now, and if you want free all memory, use function
 * cubrid_close_request.
 *
 * @param resource $req_identifier This is the request identifier.
 * @throws CubridException
 *
 */
function cubrid_free_result($req_identifier): void
{
    error_clear_last();
    $result = \cubrid_free_result($req_identifier);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
}


/**
 * This function returns the current CUBRID connection charset and is similar
 * to the CUBRID MySQL compatible function
 * cubrid_client_encoding.
 *
 * @param resource $conn_identifier The CUBRID connection.
 * @return string A string that represents the CUBRID connection charset; on success.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_get_charset($conn_identifier): string
{
    error_clear_last();
    $result = \cubrid_get_charset($conn_identifier);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * This function returns a string that represents the client library version.
 *
 * @return string A string that represents the client library version; on success.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_get_client_info(): string
{
    error_clear_last();
    $result = \cubrid_get_client_info();
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * This function returns the CUBRID database parameters or it returns FALSE on
 * failure. It returns an associative array with the values for the following
 * parameters:
 *
 *
 * PARAM_ISOLATION_LEVEL
 * PARAM_LOCK_TIMEOUT
 * PARAM_MAX_STRING_LENGTH
 * PARAM_AUTO_COMMIT
 *
 *
 *
 * Database parameters
 *
 *
 *
 * Parameter
 * Description
 *
 *
 *
 *
 * PARAM_ISOLATION_LEVEL
 * The transaction isolation level.
 *
 *
 * LOCK_TIMEOUT
 * CUBRID provides the lock timeout feature, which sets the waiting
 * time (in seconds) for the lock until the transaction lock setting is
 * allowed. The default value of the lock_timeout_in_secs parameter is
 * -1, which means the application client will wait indefinitely until
 * the transaction lock is allowed.
 *
 *
 *
 * PARAM_AUTO_COMMIT
 * In CUBRID PHP, auto-commit mode is disabled by default for
 * transaction management. It can be set by using
 * cubrid_set_autocommit.
 *
 *
 *
 *
 *
 *
 * The following table shows the isolation levels from 1 to 6. It consists of
 * table schema (row) and isolation level:
 *
 * Levels of Isolation Supported by CUBRID
 *
 *
 *
 * Name
 * Description
 *
 *
 *
 *
 * SERIALIZABLE (6)
 * In this isolation level, problems concerning concurrency (e.g.
 * dirty read, non-repeatable read, phantom read, etc.) do not
 * occur.
 *
 *
 * REPEATABLE READ CLASS with REPEATABLE READ INSTANCES (5)
 * Another transaction T2 cannot update the schema of table A while
 * transaction T1 is viewing table A.
 * Transaction T1 may experience phantom read for the record R that was
 * inserted by another transaction T2 when it is repeatedly retrieving a
 * specific record.
 *
 *
 * REPEATABLE READ CLASS with READ COMMITTED INSTANCES (or CURSOR STABILITY) (4)
 * Another transaction T2 cannot update the schema of table A while
 * transaction T1 is viewing table A.
 * Transaction T1 may experience R read (non-repeatable read) that was
 * updated and committed by another transaction T2 when it is repeatedly
 * retrieving the record R.
 *
 *
 * REPEATABLE READ CLASS with READ UNCOMMITTED INSTANCES (3)
 * Default isolation level.  Another transaction T2 cannot update
 * the schema of table A  while transaction T1 is viewing table A.
 * Transaction T1 may experience R' read (dirty read) for the record that
 * was updated but not committed by another transaction T2.
 *
 *
 * READ COMMITTED CLASS with READ COMMITTED INSTANCES (2)
 * Transaction T1 may experience A' read (non-repeatable read) for
 * the table that was updated and committed by another transaction  T2
 * while it is viewing table A repeatedly.  Transaction T1 may experience
 * R' read (non-repeatable read) for the record that was updated and
 * committed by another transaction T2 while it is retrieving the record
 * R repeatedly.
 *
 *
 * READ COMMITTED CLASS with READ UNCOMMITTED INSTANCES (1)
 * Transaction T1 may experience A' read (non-repeatable read) for
 * the table that was updated and committed by another transaction T2
 * while it is repeatedly viewing table A.  Transaction T1 may experience
 * R' read (dirty read) for the record that was updated but not committed
 * by another transaction T2.
 *
 *
 *
 *
 *
 * @param resource $conn_identifier The CUBRID connection. If the connection identifier is not specified,
 * the last link opened by cubrid_connect is assumed.
 * @return array An associative array with CUBRID database parameters; on success.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_get_db_parameter($conn_identifier): array
{
    error_clear_last();
    $result = \cubrid_get_db_parameter($conn_identifier);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * This function returns a string that represents the CUBRID server version.
 *
 * @param resource $conn_identifier The CUBRID connection.
 * @return string A string that represents the CUBRID server version; on success.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_get_server_info($conn_identifier): string
{
    error_clear_last();
    $result = \cubrid_get_server_info($conn_identifier);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * The cubrid_insert_id function retrieves the ID
 * generated for the AUTO_INCREMENT column which is updated by the previous
 * INSERT query. It returns 0 if the previous query does not generate new
 * rows.
 *
 * @param resource $conn_identifier The connection identifier previously obtained by a call to
 * cubrid_connect.
 * @return string A string representing the ID generated for an AUTO_INCREMENT column by the
 * previous query, on success.
 *
 * 0, if the previous query does not generate new rows.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_insert_id($conn_identifier = null): string
{
    error_clear_last();
    if ($conn_identifier !== null) {
        $result = \cubrid_insert_id($conn_identifier);
    } else {
        $result = \cubrid_insert_id();
    }
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * The cubrid_lob2_new function is used to create a lob object (both BLOB and CLOB).
 * This function should be used before you bind a lob object.
 *
 * @param resource $conn_identifier Connection identifier. If the connection identifier is not specified,
 * the last connection opened by cubrid_connect or
 * cubrid_connect_with_url is assumed.
 * @param string $type It may be "BLOB" or "CLOB", it won't be case-sensitive. The default value is "BLOB".
 * @return resource Lob identifier when it is successful.
 *
 * FALSE  on failure.
 * @throws CubridException
 *
 */
function cubrid_lob2_new($conn_identifier = null, string $type = "BLOB")
{
    error_clear_last();
    if ($type !== "BLOB") {
        $result = \cubrid_lob2_new($conn_identifier, $type);
    } elseif ($conn_identifier !== null) {
        $result = \cubrid_lob2_new($conn_identifier);
    } else {
        $result = \cubrid_lob2_new();
    }
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * The cubrid_lob2_size function is used to get the size of a lob object.
 *
 * @param resource $lob_identifier Lob identifier as a result of cubrid_lob2_new or get from the result set.
 * @return int It will return the size of the LOB object when it processes successfully.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_lob2_size($lob_identifier): int
{
    error_clear_last();
    $result = \cubrid_lob2_size($lob_identifier);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * The cubrid_lob2_size64 function is used to get the
 * size of a lob object.  If the size of a lob object is larger than an
 * integer data can be stored, you can use this function and it will return
 * the size as a string.
 *
 * @param resource $lob_identifier Lob identifier as a result of cubrid_lob2_new or get from the result set.
 * @return string It will return the size of the LOB object as a string when it processes successfully.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_lob2_size64($lob_identifier): string
{
    error_clear_last();
    $result = \cubrid_lob2_size64($lob_identifier);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * The cubrid_lob2_tell function is used to tell the cursor position of the LOB object.
 *
 * @param resource $lob_identifier Lob identifier as a result of cubrid_lob2_new or get from the result set.
 * @return int It will return the cursor position on the LOB object when it processes successfully.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_lob2_tell($lob_identifier): int
{
    error_clear_last();
    $result = \cubrid_lob2_tell($lob_identifier);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * The cubrid_lob2_tell64 function is used to tell the
 * cursor position of the LOB object. If the size of a lob object is larger
 * than an integer data can be stored, you can use this function and it will
 * return the position information as a string.
 *
 * @param resource $lob_identifier Lob identifier as a result of cubrid_lob2_new or get from the result set.
 * @return string It will return the cursor position on the LOB object as a string when it processes successfully.
 *
 * FALSE on failure.
 * @throws CubridException
 *
 */
function cubrid_lob2_tell64($lob_identifier): string
{
    error_clear_last();
    $result = \cubrid_lob2_tell64($lob_identifier);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
    return $result;
}


/**
 * The cubrid_set_db_parameter function is used to set
 * the CUBRID database parameters. It can set the following CUBRID database
 * parameters:
 *
 *
 * PARAM_ISOLATION_LEVEL
 * PARAM_LOCK_TIMEOUT
 *
 *
 * @param resource $conn_identifier The CUBRID connection. If the connection identifier is not specified,
 * the last link opened by cubrid_connect is assumed.
 * @param int $param_type Database parameter type.
 * @param int $param_value Isolation level value (1-6) or lock timeout (in seconds) value.
 * @throws CubridException
 *
 */
function cubrid_set_db_parameter($conn_identifier, int $param_type, int $param_value): void
{
    error_clear_last();
    $result = \cubrid_set_db_parameter($conn_identifier, $param_type, $param_value);
    if ($result === false) {
        throw CubridException::createFromPhpError();
    }
}
