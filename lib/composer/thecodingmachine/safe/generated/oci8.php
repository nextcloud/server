<?php

namespace Safe;

use Safe\Exceptions\Oci8Exception;

/**
 * Binds the PHP array var_array to the Oracle
 * placeholder name, which points to an Oracle PL/SQL
 * array. Whether it will be used for input or output will be determined at
 * run-time.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @param string $name The Oracle placeholder.
 * @param array $var_array An array.
 * @param int $max_table_length Sets the maximum length both for incoming and result arrays.
 * @param int $max_item_length Sets maximum length for array items. If not specified or equals to -1,
 * oci_bind_array_by_name will find the longest
 * element in the incoming array and will use it as the maximum length.
 * @param int $type Should be used to set the type of PL/SQL array items. See list of
 * available types below:
 *
 *
 *
 *
 * SQLT_NUM - for arrays of NUMBER.
 *
 *
 *
 *
 * SQLT_INT - for arrays of INTEGER (Note: INTEGER
 * it is actually a synonym for NUMBER(38), but
 * SQLT_NUM type won't work in this case even
 * though they are synonyms).
 *
 *
 *
 *
 * SQLT_FLT - for arrays of FLOAT.
 *
 *
 *
 *
 * SQLT_AFC - for arrays of CHAR.
 *
 *
 *
 *
 * SQLT_CHR - for arrays of VARCHAR2.
 *
 *
 *
 *
 * SQLT_VCS - for arrays of VARCHAR.
 *
 *
 *
 *
 * SQLT_AVC - for arrays of CHARZ.
 *
 *
 *
 *
 * SQLT_STR - for arrays of STRING.
 *
 *
 *
 *
 * SQLT_LVC - for arrays of LONG VARCHAR.
 *
 *
 *
 *
 * SQLT_ODT - for arrays of DATE.
 *
 *
 *
 *
 * SQLT_NUM - for arrays of NUMBER.
 *
 * SQLT_INT - for arrays of INTEGER (Note: INTEGER
 * it is actually a synonym for NUMBER(38), but
 * SQLT_NUM type won't work in this case even
 * though they are synonyms).
 *
 * SQLT_FLT - for arrays of FLOAT.
 *
 * SQLT_AFC - for arrays of CHAR.
 *
 * SQLT_CHR - for arrays of VARCHAR2.
 *
 * SQLT_VCS - for arrays of VARCHAR.
 *
 * SQLT_AVC - for arrays of CHARZ.
 *
 * SQLT_STR - for arrays of STRING.
 *
 * SQLT_LVC - for arrays of LONG VARCHAR.
 *
 * SQLT_ODT - for arrays of DATE.
 * @throws Oci8Exception
 *
 */
function oci_bind_array_by_name($statement, string $name, array &$var_array, int $max_table_length, int $max_item_length = -1, int $type = SQLT_AFC): void
{
    error_clear_last();
    $result = \oci_bind_array_by_name($statement, $name, $var_array, $max_table_length, $max_item_length, $type);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Binds a PHP variable variable to the Oracle
 * bind variable placeholder bv_name.  Binding
 * is important for Oracle database performance and also as a way to
 * avoid SQL Injection security issues.
 *
 * Binding allows the database to reuse the statement context and
 * caches from previous executions of the statement, even if another
 * user or process originally executed it.  Binding reduces SQL
 * Injection concerns because the data associated with a bind
 * variable is never treated as part of the SQL statement.  It does
 * not need quoting or escaping.
 *
 * PHP variables that have been bound can be changed and the
 * statement re-executed without needing to re-parse the statement or
 * re-bind.
 *
 * In Oracle, bind variables are commonly divided
 * into IN binds for values that are passed into
 * the database, and OUT binds for values that are
 * returned to PHP.  A bind variable may be
 * both IN and OUT.  Whether a
 * bind variable will be used for input or output is determined at
 * run-time.
 *
 * You must specify maxlength when using
 * an OUT bind so that PHP allocates enough memory
 * to hold the returned value.
 *
 * For IN binds it is recommended to set
 * the maxlength length if the statement is
 * re-executed multiple times with different values for the PHP
 * variable.  Otherwise Oracle may truncate data to the length of the
 * initial PHP variable value.  If you don't know what the maximum
 * length will be, then re-call oci_bind_by_name
 * with the current data size prior to
 * each oci_execute call. Binding an
 * unnecessarily large length will have an impact on process memory
 * in the database.
 *
 * A bind call tells Oracle which memory address to read data from.
 * For IN binds that address needs to contain
 * valid data when oci_execute is called.  This
 * means that the variable bound must remain in scope until
 * execution.  If it doesn't, unexpected results or errors such as
 * "ORA-01460: unimplemented or unreasonable conversion requested"
 * may occur.  For OUT binds one symptom is no
 * value being set in the PHP variable.
 *
 * For a statement that is repeatedly executed, binding values that
 * never change may reduce the ability of the Oracle optimizer to
 * choose the best statement execution plan.  Long running statements
 * that are rarely re-executed may not benefit from binding.  However
 * in both cases, binding might be safer than joining strings into a
 * SQL statement, as this can be a security risk if unfiltered user
 * text is concatenated.
 *
 * @param resource $statement A valid OCI8 statement identifer.
 * @param string $bv_name The colon-prefixed bind variable placeholder used in the
 * statement.  The colon is optional
 * in bv_name. Oracle does not use question
 * marks for placeholders.
 * @param mixed $variable The PHP variable to be associated with bv_name
 * @param int $maxlength Sets the maximum length for the data. If you set it to -1, this
 * function will use the current length
 * of variable to set the maximum
 * length. In this case the variable must
 * exist and contain data
 * when oci_bind_by_name is called.
 * @param int $type The datatype that Oracle will treat the data as.  The
 * default type used
 * is SQLT_CHR. Oracle will convert the data
 * between this type and the database column (or PL/SQL variable
 * type), when possible.
 *
 * If you need to bind an abstract datatype (LOB/ROWID/BFILE) you
 * need to allocate it first using the
 * oci_new_descriptor function. The
 * length is not used for abstract datatypes
 * and should be set to -1.
 *
 * Possible values for type are:
 *
 *
 *
 * SQLT_BFILEE or OCI_B_BFILE
 * - for BFILEs;
 *
 *
 *
 *
 * SQLT_CFILEE or OCI_B_CFILEE
 * - for CFILEs;
 *
 *
 *
 *
 * SQLT_CLOB or OCI_B_CLOB
 * - for CLOBs;
 *
 *
 *
 *
 * SQLT_BLOB or OCI_B_BLOB
 * - for BLOBs;
 *
 *
 *
 *
 * SQLT_RDD or OCI_B_ROWID
 * - for ROWIDs;
 *
 *
 *
 *
 * SQLT_NTY or OCI_B_NTY
 * - for named datatypes;
 *
 *
 *
 *
 * SQLT_INT or OCI_B_INT - for integers;
 *
 *
 *
 *
 * SQLT_CHR - for VARCHARs;
 *
 *
 *
 *
 * SQLT_BIN or OCI_B_BIN
 * - for RAW columns;
 *
 *
 *
 *
 * SQLT_LNG - for LONG columns;
 *
 *
 *
 *
 * SQLT_LBI - for LONG RAW columns;
 *
 *
 *
 *
 * SQLT_RSET - for cursors created
 * with oci_new_cursor;
 *
 *
 *
 *
 * SQLT_BOL or OCI_B_BOL
 * - for PL/SQL BOOLEANs (Requires OCI8 2.0.7 and Oracle Database 12c)
 *
 *
 *
 *
 * SQLT_BFILEE or OCI_B_BFILE
 * - for BFILEs;
 *
 * SQLT_CFILEE or OCI_B_CFILEE
 * - for CFILEs;
 *
 * SQLT_CLOB or OCI_B_CLOB
 * - for CLOBs;
 *
 * SQLT_BLOB or OCI_B_BLOB
 * - for BLOBs;
 *
 * SQLT_RDD or OCI_B_ROWID
 * - for ROWIDs;
 *
 * SQLT_NTY or OCI_B_NTY
 * - for named datatypes;
 *
 * SQLT_INT or OCI_B_INT - for integers;
 *
 * SQLT_CHR - for VARCHARs;
 *
 * SQLT_BIN or OCI_B_BIN
 * - for RAW columns;
 *
 * SQLT_LNG - for LONG columns;
 *
 * SQLT_LBI - for LONG RAW columns;
 *
 * SQLT_RSET - for cursors created
 * with oci_new_cursor;
 *
 * SQLT_BOL or OCI_B_BOL
 * - for PL/SQL BOOLEANs (Requires OCI8 2.0.7 and Oracle Database 12c)
 * @throws Oci8Exception
 *
 */
function oci_bind_by_name($statement, string $bv_name, &$variable, int $maxlength = -1, int $type = SQLT_CHR): void
{
    error_clear_last();
    $result = \oci_bind_by_name($statement, $bv_name, $variable, $maxlength, $type);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Invalidates a cursor, freeing all associated resources and cancels the
 * ability to read from it.
 *
 * @param resource $statement An OCI statement.
 * @throws Oci8Exception
 *
 */
function oci_cancel($statement): void
{
    error_clear_last();
    $result = \oci_cancel($statement);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Unsets connection.  The underlying database
 * connection is closed if no other resources are using it and if it
 * was created with oci_connect
 * or oci_new_connect.
 *
 * It is recommended to close connections that are no longer needed
 * because this makes database resources available for other users.
 *
 * @param resource $connection An Oracle connection identifier returned by
 * oci_connect, oci_pconnect,
 * or oci_new_connect.
 * @throws Oci8Exception
 *
 */
function oci_close($connection): void
{
    error_clear_last();
    $result = \oci_close($connection);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Commits the outstanding transaction for the
 * Oracle connection.  A commit ends the
 * current transaction and makes permanent all changes.  It releases
 * all locks held.
 *
 * A transaction begins when the first SQL statement that changes data
 * is executed with oci_execute using
 * the OCI_NO_AUTO_COMMIT flag.  Further data
 * changes made by other statements become part of the same
 * transaction.  Data changes made in a transaction are temporary
 * until the transaction is committed or rolled back.  Other users of
 * the database will not see the changes until they are committed.
 *
 * When inserting or updating data, using transactions is recommended
 * for relational data consistency and for performance reasons.
 *
 * @param resource $connection An Oracle connection identifier, returned by
 * oci_connect, oci_pconnect, or oci_new_connect.
 * @throws Oci8Exception
 *
 */
function oci_commit($connection): void
{
    error_clear_last();
    $result = \oci_commit($connection);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Returns a connection identifier needed for most other OCI8 operations.
 *
 * See Connection Handling for
 * general information on connection management and connection
 * pooling.
 *
 * From PHP 5.1.2 (PECL OCI8 1.1) oci_close can
 * be used to close the connection.
 *
 * The second and subsequent calls to oci_connect
 * with the same parameters will return the connection handle returned
 * from the first call. This means that transactions in one handle are
 * also in the other handles, because they use the
 * same underlying database connection. If two
 * handles need to be transactionally isolated from each other, use
 * oci_new_connect instead.
 *
 * @param string $username The Oracle user name.
 * @param string $password The password for username.
 * @param string $connection_string Contains
 * the Oracle instance to connect to. It can be
 * an Easy Connect
 * string, or a Connect Name from
 * the tnsnames.ora file, or the name of a local
 * Oracle instance.
 *
 * If not specified, PHP uses
 * environment variables such as TWO_TASK (on Linux)
 * or LOCAL (on Windows)
 * and ORACLE_SID to determine the
 * Oracle instance to connect to.
 *
 *
 * To use the Easy Connect naming method, PHP must be linked with Oracle
 * 10g or greater Client libraries. The Easy Connect string for Oracle
 * 10g is of the form:
 * [//]host_name[:port][/service_name]. From Oracle
 * 11g, the syntax is:
 * [//]host_name[:port][/service_name][:server_type][/instance_name].
 * Service names can be found by running the Oracle
 * utility lsnrctl status on the database server
 * machine.
 *
 *
 * The tnsnames.ora file can be in the Oracle Net
 * search path, which
 * includes $ORACLE_HOME/network/admin
 * and /etc.  Alternatively
 * set TNS_ADMIN so
 * that $TNS_ADMIN/tnsnames.ora is read.  Make sure
 * the web daemon has read access to the file.
 * @param string $character_set Determines
 * the character set used by the Oracle Client libraries.  The character
 * set does not need to match the character set used by the database.  If
 * it doesn't match, Oracle will do its best to convert data to and from
 * the database character set.  Depending on the character sets this may
 * not give usable results.  Conversion also adds some time overhead.
 *
 * If not specified, the
 * Oracle Client libraries determine a character set from
 * the NLS_LANG environment variable.
 *
 * Passing this parameter can
 * reduce the time taken to connect.
 * @param int $session_mode This
 * parameter is available since version PHP 5 (PECL OCI8 1.1) and accepts the
 * following values: OCI_DEFAULT,
 * OCI_SYSOPER and OCI_SYSDBA.
 * If either OCI_SYSOPER or
 * OCI_SYSDBA were specified, this function will try
 * to establish privileged connection using external credentials.
 * Privileged connections are disabled by default. To enable them you
 * need to set oci8.privileged_connect
 * to On.
 *
 *
 * PHP 5.3 (PECL OCI8 1.3.4) introduced the
 * OCI_CRED_EXT mode value. This tells Oracle to use
 * External or OS authentication, which must be configured in the
 * database.  The OCI_CRED_EXT flag can only be used
 * with username of "/" and a empty password.
 * oci8.privileged_connect
 * may be On or Off.
 *
 *
 * OCI_CRED_EXT may be combined with the
 * OCI_SYSOPER or
 * OCI_SYSDBA modes.
 *
 *
 * OCI_CRED_EXT is not supported on Windows for
 * security reasons.
 * @return resource Returns a connection identifier.
 * @throws Oci8Exception
 *
 */
function oci_connect(string $username, string $password, string $connection_string = null, string $character_set = null, int $session_mode = null)
{
    error_clear_last();
    if ($session_mode !== null) {
        $result = \oci_connect($username, $password, $connection_string, $character_set, $session_mode);
    } elseif ($character_set !== null) {
        $result = \oci_connect($username, $password, $connection_string, $character_set);
    } elseif ($connection_string !== null) {
        $result = \oci_connect($username, $password, $connection_string);
    } else {
        $result = \oci_connect($username, $password);
    }
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Associates a PHP variable with a column for query fetches using oci_fetch.
 *
 * The oci_define_by_name call must occur before
 * executing oci_execute.
 *
 * @param resource $statement A valid OCI8 statement
 * identifier created by oci_parse and executed
 * by oci_execute, or a REF
 * CURSOR statement identifier.
 * @param string $column_name The column name used in the query.
 *
 * Use uppercase for Oracle's default, non-case sensitive column
 * names.  Use the exact column name case for case-sensitive
 * column names.
 * @param mixed $variable The PHP variable that will contain the returned column value.
 * @param int $type The data type to be returned.  Generally not needed.  Note that
 * Oracle-style data conversions are not performed.  For example,
 * SQLT_INT will be ignored and the returned
 * data type will still be SQLT_CHR.
 *
 * You can optionally use oci_new_descriptor
 * to allocate LOB/ROWID/BFILE descriptors.
 * @throws Oci8Exception
 *
 */
function oci_define_by_name($statement, string $column_name, &$variable, int $type = SQLT_CHR): void
{
    error_clear_last();
    $result = \oci_define_by_name($statement, $column_name, $variable, $type);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Executes a statement previously returned
 * from oci_parse.
 *
 * After execution, statements like INSERT will
 * have data committed to the database by default.  For statements
 * like SELECT, execution performs the logic of the
 * query. Query results can subsequently be fetched in PHP with
 * functions like oci_fetch_array.
 *
 * Each parsed statement may be executed multiple times, saving the
 * cost of re-parsing.  This is commonly used
 * for INSERT statements when data is bound
 * with oci_bind_by_name.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @param int $mode An optional second parameter can be one of the following constants:
 *
 * Execution Modes
 *
 *
 *
 * Constant
 * Description
 *
 *
 *
 *
 * OCI_COMMIT_ON_SUCCESS
 * Automatically commit all outstanding changes for
 * this connection when the statement has succeeded. This
 * is the default.
 *
 *
 * OCI_DESCRIBE_ONLY
 * Make query meta data available to functions
 * like oci_field_name but do not
 * create a result set. Any subsequent fetch call such
 * as oci_fetch_array will
 * fail.
 *
 *
 * OCI_NO_AUTO_COMMIT
 * Do not automatically commit changes.  Prior to PHP
 * 5.3.2 (PECL OCI8 1.4)
 * use OCI_DEFAULT which is equivalent
 * to OCI_NO_AUTO_COMMIT.
 *
 *
 *
 *
 *
 * Using OCI_NO_AUTO_COMMIT mode starts or continues a
 * transaction. Transactions are automatically rolled back when
 * the connection is closed, or when the script ends.  Explicitly
 * call oci_commit to commit a transaction,
 * or oci_rollback to abort it.
 *
 * When inserting or updating data, using transactions is
 * recommended for relational data consistency and for performance
 * reasons.
 *
 * If OCI_NO_AUTO_COMMIT mode is used for any
 * statement including queries, and
 * oci_commit
 * or oci_rollback is not subsequently
 * called, then OCI8 will perform a rollback at the end of the
 * script even if no data was changed.  To avoid an unnecessary
 * rollback, many scripts do not
 * use OCI_NO_AUTO_COMMIT mode for queries or
 * PL/SQL.  Be careful to ensure the appropriate transactional
 * consistency for the application when
 * using oci_execute with different modes in
 * the same script.
 * @throws Oci8Exception
 *
 */
function oci_execute($statement, int $mode = OCI_COMMIT_ON_SUCCESS): void
{
    error_clear_last();
    $result = \oci_execute($statement, $mode);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Fetches multiple rows from a query into a two-dimensional array.
 * By default, all rows are returned.
 *
 * This function can be called only once for each query executed
 * with oci_execute.
 *
 * @param resource $statement A valid OCI8 statement
 * identifier created by oci_parse and executed
 * by oci_execute, or a REF
 * CURSOR statement identifier.
 * @param array|null $output The variable to contain the returned rows.
 *
 * LOB columns are returned as strings, where Oracle supports
 * conversion.
 *
 * See oci_fetch_array for more information
 * on how data and types are fetched.
 * @param int $skip The number of initial rows to discard when fetching the
 * result. The default value is 0, so the first row onwards is
 * returned.
 * @param int $maxrows The number of rows to return.  The default is -1 meaning return
 * all the rows from skip + 1 onwards.
 * @param int $flags Parameter flags indicates the array
 * structure and whether associative arrays should be used.
 *
 * oci_fetch_all Array Structure Modes
 *
 *
 *
 * Constant
 * Description
 *
 *
 *
 *
 * OCI_FETCHSTATEMENT_BY_ROW
 * The outer array will contain one sub-array per query
 * row.
 *
 *
 * OCI_FETCHSTATEMENT_BY_COLUMN
 * The outer array will contain one sub-array per query
 * column. This is the default.
 *
 *
 *
 *
 *
 * Arrays can be indexed either by column heading or numerically.
 * Only one index mode will be returned.
 *
 * oci_fetch_all Array Index Modes
 *
 *
 *
 * Constant
 * Description
 *
 *
 *
 *
 * OCI_NUM
 * Numeric indexes are used for each column's array.
 *
 *
 * OCI_ASSOC
 * Associative indexes are used for each column's
 * array. This is the default.
 *
 *
 *
 *
 *
 * Use the addition operator "+" to choose a combination
 * of array structure and index modes.
 *
 * Oracle's default, non-case sensitive column names will have
 * uppercase array keys.  Case-sensitive column names will have
 * array keys using the exact column case.
 * Use var_dump
 * on output to verify the appropriate case
 * to use for each query.
 *
 * Queries that have more than one column with the same name
 * should use column aliases.  Otherwise only one of the columns
 * will appear in an associative array.
 * @return int Returns the number of rows in output, which
 * may be 0 or more.
 * @throws Oci8Exception
 *
 */
function oci_fetch_all($statement, ?array &$output, int $skip = 0, int $maxrows = -1, int $flags = OCI_FETCHSTATEMENT_BY_COLUMN + OCI_ASSOC): int
{
    error_clear_last();
    $result = \oci_fetch_all($statement, $output, $skip, $maxrows, $flags);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the name of the field.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @param mixed $field Can be the field's index (1-based) or name.
 * @return string Returns the name as a strings.
 * @throws Oci8Exception
 *
 */
function oci_field_name($statement, $field): string
{
    error_clear_last();
    $result = \oci_field_name($statement, $field);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Returns precision of the field.
 *
 * For FLOAT columns, precision is nonzero and scale is -127.
 * If precision is 0, then column is NUMBER. Else it's
 * NUMBER(precision, scale).
 *
 * @param resource $statement A valid OCI statement identifier.
 * @param mixed $field Can be the field's index (1-based) or name.
 * @return int Returns the precision as an integers.
 * @throws Oci8Exception
 *
 */
function oci_field_precision($statement, $field): int
{
    error_clear_last();
    $result = \oci_field_precision($statement, $field);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the scale of the column with field index.
 *
 * For FLOAT columns, precision is nonzero and scale is -127.
 * If precision is 0, then column is NUMBER. Else it's
 * NUMBER(precision, scale).
 *
 * @param resource $statement A valid OCI statement identifier.
 * @param mixed $field Can be the field's index (1-based) or name.
 * @return int Returns the scale as an integers.
 * @throws Oci8Exception
 *
 */
function oci_field_scale($statement, $field): int
{
    error_clear_last();
    $result = \oci_field_scale($statement, $field);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the size of a field.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @param mixed $field Can be the field's index (1-based) or name.
 * @return int Returns the size of a field in bytess.
 * @throws Oci8Exception
 *
 */
function oci_field_size($statement, $field): int
{
    error_clear_last();
    $result = \oci_field_size($statement, $field);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Returns Oracle's raw "SQLT" data type of the field.
 *
 * If you want a field's type name, then use oci_field_type instead.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @param mixed $field Can be the field's index (1-based) or name.
 * @return int Returns Oracle's raw data type as a numbers.
 * @throws Oci8Exception
 *
 */
function oci_field_type_raw($statement, $field): int
{
    error_clear_last();
    $result = \oci_field_type_raw($statement, $field);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Returns a field's data type name.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @param mixed $field Can be the field's index (1-based) or name.
 * @return mixed Returns the field data type as a strings.
 * @throws Oci8Exception
 *
 */
function oci_field_type($statement, $field)
{
    error_clear_last();
    $result = \oci_field_type($statement, $field);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Frees a descriptor allocated by oci_new_descriptor.
 *
 * @param resource $descriptor
 * @throws Oci8Exception
 *
 */
function oci_free_descriptor($descriptor): void
{
    error_clear_last();
    $result = \oci_free_descriptor($descriptor);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Frees resources associated with Oracle's cursor or statement, which was
 * received from as a result of oci_parse or obtained
 * from Oracle.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @throws Oci8Exception
 *
 */
function oci_free_statement($statement): void
{
    error_clear_last();
    $result = \oci_free_statement($statement);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Allocates a new collection object.
 *
 * @param resource $connection An Oracle connection identifier, returned by
 * oci_connect or oci_pconnect.
 * @param string $tdo Should be a valid named type (uppercase).
 * @param string $schema Should point to the scheme, where the named type was created. The name
 * of the current user is the default value.
 * @return \OCI-Collection Returns a new OCICollection object.
 * @throws Oci8Exception
 *
 */
function oci_new_collection($connection, string $tdo, string $schema = null)
{
    error_clear_last();
    $result = \oci_new_collection($connection, $tdo, $schema);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Establishes a new connection to an Oracle server and logs on.
 *
 * Unlike oci_connect and
 * oci_pconnect, oci_new_connect
 * does not cache connections and will always return a brand-new freshly
 * opened connection handle. This is useful if your application needs
 * transactional isolation between two sets of queries.
 *
 * @param string $username The Oracle user name.
 * @param string $password The password for username.
 * @param string $connection_string Contains
 * the Oracle instance to connect to. It can be
 * an Easy Connect
 * string, or a Connect Name from
 * the tnsnames.ora file, or the name of a local
 * Oracle instance.
 *
 * If not specified, PHP uses
 * environment variables such as TWO_TASK (on Linux)
 * or LOCAL (on Windows)
 * and ORACLE_SID to determine the
 * Oracle instance to connect to.
 *
 *
 * To use the Easy Connect naming method, PHP must be linked with Oracle
 * 10g or greater Client libraries. The Easy Connect string for Oracle
 * 10g is of the form:
 * [//]host_name[:port][/service_name]. From Oracle
 * 11g, the syntax is:
 * [//]host_name[:port][/service_name][:server_type][/instance_name].
 * Service names can be found by running the Oracle
 * utility lsnrctl status on the database server
 * machine.
 *
 *
 * The tnsnames.ora file can be in the Oracle Net
 * search path, which
 * includes $ORACLE_HOME/network/admin
 * and /etc.  Alternatively
 * set TNS_ADMIN so
 * that $TNS_ADMIN/tnsnames.ora is read.  Make sure
 * the web daemon has read access to the file.
 * @param string $character_set Determines
 * the character set used by the Oracle Client libraries.  The character
 * set does not need to match the character set used by the database.  If
 * it doesn't match, Oracle will do its best to convert data to and from
 * the database character set.  Depending on the character sets this may
 * not give usable results.  Conversion also adds some time overhead.
 *
 * If not specified, the
 * Oracle Client libraries determine a character set from
 * the NLS_LANG environment variable.
 *
 * Passing this parameter can
 * reduce the time taken to connect.
 * @param int $session_mode This
 * parameter is available since version PHP 5 (PECL OCI8 1.1) and accepts the
 * following values: OCI_DEFAULT,
 * OCI_SYSOPER and OCI_SYSDBA.
 * If either OCI_SYSOPER or
 * OCI_SYSDBA were specified, this function will try
 * to establish privileged connection using external credentials.
 * Privileged connections are disabled by default. To enable them you
 * need to set oci8.privileged_connect
 * to On.
 *
 *
 * PHP 5.3 (PECL OCI8 1.3.4) introduced the
 * OCI_CRED_EXT mode value. This tells Oracle to use
 * External or OS authentication, which must be configured in the
 * database.  The OCI_CRED_EXT flag can only be used
 * with username of "/" and a empty password.
 * oci8.privileged_connect
 * may be On or Off.
 *
 *
 * OCI_CRED_EXT may be combined with the
 * OCI_SYSOPER or
 * OCI_SYSDBA modes.
 *
 *
 * OCI_CRED_EXT is not supported on Windows for
 * security reasons.
 * @return resource Returns a connection identifier.
 * @throws Oci8Exception
 *
 */
function oci_new_connect(string $username, string $password, string $connection_string = null, string $character_set = null, int $session_mode = null)
{
    error_clear_last();
    if ($session_mode !== null) {
        $result = \oci_new_connect($username, $password, $connection_string, $character_set, $session_mode);
    } elseif ($character_set !== null) {
        $result = \oci_new_connect($username, $password, $connection_string, $character_set);
    } elseif ($connection_string !== null) {
        $result = \oci_new_connect($username, $password, $connection_string);
    } else {
        $result = \oci_new_connect($username, $password);
    }
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Allocates a new statement handle on the specified connection.
 *
 * @param resource $connection An Oracle connection identifier, returned by
 * oci_connect or oci_pconnect.
 * @return resource Returns a new statement handle.
 * @throws Oci8Exception
 *
 */
function oci_new_cursor($connection)
{
    error_clear_last();
    $result = \oci_new_cursor($connection);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Allocates resources to hold descriptor or LOB locator.
 *
 * @param resource $connection An Oracle connection identifier, returned by
 * oci_connect or oci_pconnect.
 * @param int $type Valid values for type are:
 * OCI_DTYPE_FILE, OCI_DTYPE_LOB and
 * OCI_DTYPE_ROWID.
 * @return \OCI-Lob Returns a new LOB or FILE descriptor on success, FALSE on error.
 * @throws Oci8Exception
 *
 */
function oci_new_descriptor($connection, int $type = OCI_DTYPE_LOB)
{
    error_clear_last();
    $result = \oci_new_descriptor($connection, $type);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Gets the number of columns in the given statement.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @return int Returns the number of columns as an integers.
 * @throws Oci8Exception
 *
 */
function oci_num_fields($statement): int
{
    error_clear_last();
    $result = \oci_num_fields($statement);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Gets the number of rows affected during statement execution.
 *
 * @param resource $statement A valid OCI statement identifier.
 * @return int Returns the number of rows affected as an integers.
 * @throws Oci8Exception
 *
 */
function oci_num_rows($statement): int
{
    error_clear_last();
    $result = \oci_num_rows($statement);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Prepares sql_text using
 * connection and returns the statement identifier,
 * which can be used with oci_bind_by_name,
 * oci_execute and other functions.
 *
 * Statement identifiers can be freed
 * with oci_free_statement or by setting the
 * variable to NULL.
 *
 * @param resource $connection An Oracle connection identifier, returned by
 * oci_connect, oci_pconnect, or oci_new_connect.
 * @param string $sql_text The SQL or PL/SQL statement.
 *
 * SQL statements should not end with a
 * semi-colon (";").  PL/SQL
 * statements should end with a semi-colon
 * (";").
 * @return resource Returns a statement handle on success.
 * @throws Oci8Exception
 *
 */
function oci_parse($connection, string $sql_text)
{
    error_clear_last();
    $result = \oci_parse($connection, $sql_text);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Creates a persistent connection to an Oracle server and logs on.
 *
 * Persistent connections are cached and re-used between requests, resulting
 * in reduced overhead on each page load; a typical PHP application will have
 * a single persistent connection open against an  Oracle server per Apache
 * child process (or PHP FastCGI/CGI process). See the Persistent Database
 * Connections section for more information.
 *
 * @param string $username The Oracle user name.
 * @param string $password The password for username.
 * @param string $connection_string Contains
 * the Oracle instance to connect to. It can be
 * an Easy Connect
 * string, or a Connect Name from
 * the tnsnames.ora file, or the name of a local
 * Oracle instance.
 *
 * If not specified, PHP uses
 * environment variables such as TWO_TASK (on Linux)
 * or LOCAL (on Windows)
 * and ORACLE_SID to determine the
 * Oracle instance to connect to.
 *
 *
 * To use the Easy Connect naming method, PHP must be linked with Oracle
 * 10g or greater Client libraries. The Easy Connect string for Oracle
 * 10g is of the form:
 * [//]host_name[:port][/service_name]. From Oracle
 * 11g, the syntax is:
 * [//]host_name[:port][/service_name][:server_type][/instance_name].
 * Service names can be found by running the Oracle
 * utility lsnrctl status on the database server
 * machine.
 *
 *
 * The tnsnames.ora file can be in the Oracle Net
 * search path, which
 * includes $ORACLE_HOME/network/admin
 * and /etc.  Alternatively
 * set TNS_ADMIN so
 * that $TNS_ADMIN/tnsnames.ora is read.  Make sure
 * the web daemon has read access to the file.
 * @param string $character_set Determines
 * the character set used by the Oracle Client libraries.  The character
 * set does not need to match the character set used by the database.  If
 * it doesn't match, Oracle will do its best to convert data to and from
 * the database character set.  Depending on the character sets this may
 * not give usable results.  Conversion also adds some time overhead.
 *
 * If not specified, the
 * Oracle Client libraries determine a character set from
 * the NLS_LANG environment variable.
 *
 * Passing this parameter can
 * reduce the time taken to connect.
 * @param int $session_mode This
 * parameter is available since version PHP 5 (PECL OCI8 1.1) and accepts the
 * following values: OCI_DEFAULT,
 * OCI_SYSOPER and OCI_SYSDBA.
 * If either OCI_SYSOPER or
 * OCI_SYSDBA were specified, this function will try
 * to establish privileged connection using external credentials.
 * Privileged connections are disabled by default. To enable them you
 * need to set oci8.privileged_connect
 * to On.
 *
 *
 * PHP 5.3 (PECL OCI8 1.3.4) introduced the
 * OCI_CRED_EXT mode value. This tells Oracle to use
 * External or OS authentication, which must be configured in the
 * database.  The OCI_CRED_EXT flag can only be used
 * with username of "/" and a empty password.
 * oci8.privileged_connect
 * may be On or Off.
 *
 *
 * OCI_CRED_EXT may be combined with the
 * OCI_SYSOPER or
 * OCI_SYSDBA modes.
 *
 *
 * OCI_CRED_EXT is not supported on Windows for
 * security reasons.
 * @return resource Returns a connection identifier.
 * @throws Oci8Exception
 *
 */
function oci_pconnect(string $username, string $password, string $connection_string = null, string $character_set = null, int $session_mode = null)
{
    error_clear_last();
    if ($session_mode !== null) {
        $result = \oci_pconnect($username, $password, $connection_string, $character_set, $session_mode);
    } elseif ($character_set !== null) {
        $result = \oci_pconnect($username, $password, $connection_string, $character_set);
    } elseif ($connection_string !== null) {
        $result = \oci_pconnect($username, $password, $connection_string);
    } else {
        $result = \oci_pconnect($username, $password);
    }
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the data from field in the current row,
 * fetched by oci_fetch.
 *
 * For details on the data type mapping performed by
 * the OCI8 extension, see the datatypes
 * supported by the driver
 *
 * @param resource $statement
 * @param mixed $field Can be either use the column number (1-based) or the column name.
 * The case of the column name must be the case that Oracle meta data
 * describes the column as, which is uppercase for columns created
 * case insensitively.
 * @return string Returns everything as strings except for abstract types (ROWIDs, LOBs and
 * FILEs).
 * @throws Oci8Exception
 *
 */
function oci_result($statement, $field): string
{
    error_clear_last();
    $result = \oci_result($statement, $field);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Reverts all uncommitted changes for the Oracle
 * connection and ends the transaction.  It
 * releases all locks held.  All Oracle SAVEPOINTS
 * are erased.
 *
 * A transaction begins when the first SQL statement that changes data
 * is executed with oci_execute using
 * the OCI_NO_AUTO_COMMIT flag.  Further data
 * changes made by other statements become part of the same
 * transaction.  Data changes made in a transaction are temporary
 * until the transaction is committed or rolled back.  Other users of
 * the database will not see the changes until they are committed.
 *
 * When inserting or updating data, using transactions is recommended
 * for relational data consistency and for performance reasons.
 *
 * @param resource $connection An Oracle connection identifier, returned by
 * oci_connect, oci_pconnect
 * or oci_new_connect.
 * @throws Oci8Exception
 *
 */
function oci_rollback($connection): void
{
    error_clear_last();
    $result = \oci_rollback($connection);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Returns a string with the Oracle Database version and available options
 *
 * @param resource $connection
 * @return string Returns the version information as a string.
 * @throws Oci8Exception
 *
 */
function oci_server_version($connection): string
{
    error_clear_last();
    $result = \oci_server_version($connection);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Sets the action name for Oracle tracing.
 *
 * The action name is registered with the database when the next
 * 'round-trip' from PHP to the database occurs, typically when an SQL
 * statement is executed.
 *
 * The action name can subsequently be queried from database administration
 * views such as V$SESSION.  It can be used for
 * tracing and monitoring such as with V$SQLAREA
 * and DBMS_MONITOR.SERV_MOD_ACT_STAT_ENABLE.
 *
 * The value may be retained across persistent connections.
 *
 * @param resource $connection An Oracle connection identifier,
 * returned by oci_connect, oci_pconnect,
 * or oci_new_connect.
 * @param string $action_name User chosen string up to 32 bytes long.
 * @throws Oci8Exception
 *
 */
function oci_set_action($connection, string $action_name): void
{
    error_clear_last();
    $result = \oci_set_action($connection, $action_name);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Sets a timeout limiting the maxium time a database round-trip using this connection may take.
 *
 * Each OCI8 operation may make zero or more calls to Oracle's client
 * library.  These internal calls may then may make zero or more
 * round-trips to Oracle Database.  If any one of those round-trips
 * takes more than time_out milliseconds, then the
 * operation is cancelled and an error is returned to the application.
 *
 * The time_out value applies to each round-trip
 * individually, not to the sum of all round-trips.  Time spent
 * processing in PHP OCI8 before or after the completion of each
 * round-trip is not counted.
 *
 * When a call is interrupted, Oracle will attempt to clean up the
 * connection for reuse.  This operation is allowed to run for
 * another time_out period.  Depending on the
 * outcome of the cleanup, the connection may or may not be reusable.
 *
 * When persistent connections are used, the timeout value will be
 * retained across PHP requests.
 *
 * The oci_set_call_timeout function is available
 * when OCI8 uses Oracle 18 (or later) Client libraries.
 *
 * @param resource $connection An Oracle connection identifier,
 * returned by oci_connect, oci_pconnect,
 * or oci_new_connect.
 * @param int $time_out The maximum time in milliseconds that any single round-trip between PHP and Oracle Database may take.
 * @throws Oci8Exception
 *
 */
function oci_set_call_timeout($connection, int $time_out): void
{
    error_clear_last();
    $result = \oci_set_call_timeout($connection, $time_out);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Sets the client identifier used by various database components to
 * identify lightweight application users who authenticate as the same
 * database user.
 *
 * The client identifier is registered with the database when the next
 * 'round-trip' from PHP to the database occurs, typically when an SQL
 * statement is executed.
 *
 * The identifier can subsequently be queried, for example
 * with SELECT SYS_CONTEXT('USERENV','CLIENT_IDENTIFIER')
 * FROM DUAL.  Database administration views such
 * as V$SESSION will also contain the value.  It
 * can be used with DBMS_MONITOR.CLIENT_ID_TRACE_ENABLE
 * for tracing and can also be used for auditing.
 *
 * The value may be retained across page requests that use the same persistent connection.
 *
 * @param resource $connection An Oracle connection identifier,
 * returned by oci_connect, oci_pconnect,
 * or oci_new_connect.
 * @param string $client_identifier User chosen string up to 64 bytes long.
 * @throws Oci8Exception
 *
 */
function oci_set_client_identifier($connection, string $client_identifier): void
{
    error_clear_last();
    $result = \oci_set_client_identifier($connection, $client_identifier);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Sets the client information for Oracle tracing.
 *
 * The client information is registered with the database when the next
 * 'round-trip' from PHP to the database occurs, typically when an SQL
 * statement is executed.
 *
 * The client information can subsequently be queried from database
 * administration views such as V$SESSION.
 *
 * The value may be retained across persistent connections.
 *
 * @param resource $connection An Oracle connection identifier,
 * returned by oci_connect, oci_pconnect,
 * or oci_new_connect.
 * @param string $client_info User chosen string up to 64 bytes long.
 * @throws Oci8Exception
 *
 */
function oci_set_client_info($connection, string $client_info): void
{
    error_clear_last();
    $result = \oci_set_client_info($connection, $client_info);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Sets the DBOP for Oracle tracing.
 *
 * The database operation name is registered with the database when the next
 * 'round-trip' from PHP to the database occurs, typically when a SQL
 * statement is executed.
 *
 * The database operation can subsequently be queried from database administration
 * views such as V$SQL_MONITOR.
 *
 * The oci_set_db_operation function is available
 * when OCI8 uses Oracle 12 (or later) Client libraries and Oracle Database 12 (or later).
 *
 * @param resource $connection An Oracle connection identifier,
 * returned by oci_connect, oci_pconnect,
 * or oci_new_connect.
 * @param string $dbop User chosen string.
 * @throws Oci8Exception
 *
 */
function oci_set_db_operation($connection, string $dbop): void
{
    error_clear_last();
    $result = \oci_set_db_operation($connection, $dbop);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Sets the database "edition" of objects to be used by a subsequent
 * connections.
 *
 * Oracle Editions allow concurrent versions of applications to run
 * using the same schema and object names.  This is useful for
 * upgrading live systems.
 *
 * Call oci_set_edition before calling
 * oci_connect, oci_pconnect
 * or oci_new_connect.
 *
 * If an edition is set that is not valid in the database, connection
 * will fail even if oci_set_edition returns success.
 *
 * When using persistent connections, if a connection with the
 * requested edition setting already exists, it is reused.  Otherwise,
 * a different persistent connection is created
 *
 * @param string $edition Oracle Database edition name previously created with the SQL
 * "CREATE EDITION" command.
 * @throws Oci8Exception
 *
 */
function oci_set_edition(string $edition): void
{
    error_clear_last();
    $result = \oci_set_edition($edition);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Sets the module name for Oracle tracing.
 *
 * The module name is registered with the database when the next
 * 'round-trip' from PHP to the database occurs, typically when an SQL
 * statement is executed.
 *
 * The name can subsequently be queried from database administration
 * views such as V$SESSION.  It can be used for
 * tracing and monitoring such as with V$SQLAREA
 * and DBMS_MONITOR.SERV_MOD_ACT_STAT_ENABLE.
 *
 * The value may be retained across persistent connections.
 *
 * @param resource $connection An Oracle connection identifier,
 * returned by oci_connect, oci_pconnect,
 * or oci_new_connect.
 * @param string $module_name User chosen string up to 48 bytes long.
 * @throws Oci8Exception
 *
 */
function oci_set_module_name($connection, string $module_name): void
{
    error_clear_last();
    $result = \oci_set_module_name($connection, $module_name);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Sets the number of rows to be buffered by the Oracle Client
 * libraries after a successful query call
 * to oci_execute and for each subsequent
 * internal fetch request to the database.  For queries returning a
 * large number of rows, performance can be significantly improved by
 * increasing the prefetch count above the
 * default oci8.default_prefetch
 * value.
 *
 * Prefetching is Oracle's efficient way of returning more than one
 * data row from the database in each network request.  This can
 * result in better network and CPU utilization.  The buffering of
 * rows is internal to OCI8 and the behavior of OCI8 fetching
 * functions is unchanged regardless of the prefetch count. For
 * example, oci_fetch_row will always return one
 * row.  The prefetch buffer is per-statement and is not used by
 * re-executed statements or by other connections.
 *
 * Call oci_set_prefetch before
 * calling oci_execute.
 *
 * A tuning goal is to set the prefetch value to a reasonable size for
 * the network and database to handle.  For queries returning a very
 * large number of rows, overall system efficiency might be better if
 * rows are retrieved from the database in several chunks (i.e set the
 * prefetch value smaller than the number of rows).  This allows the
 * database to handle other users' statements while the PHP script is
 * processing the current set of rows.
 *
 * Query prefetching was introduced in Oracle 8i.  REF CURSOR
 * prefetching was introduced in Oracle 11gR2 and occurs when PHP is
 * linked with Oracle 11gR2 (or later) Client libraries.
 * Nested cursor prefetching was
 * introduced in Oracle 11gR2 and requires both the Oracle Client
 * libraries and the database to be version 11gR2 or greater.
 *
 * Prefetching is not supported when queries contain LONG or LOB
 * columns.  The prefetch value is ignored and single-row fetches will
 * be used in all the situations when prefetching is not supported.
 *
 * When using Oracle Database 12c, the prefetch
 * value set by PHP can be overridden by Oracle's
 * client oraaccess.xml configuration file.  Refer
 * to Oracle documentation for more detail.
 *
 * @param resource $statement A valid OCI8 statement
 * identifier created by oci_parse and executed
 * by oci_execute, or a REF
 * CURSOR statement identifier.
 * @param int $rows The number of rows to be prefetched, &gt;= 0
 * @throws Oci8Exception
 *
 */
function oci_set_prefetch($statement, int $rows): void
{
    error_clear_last();
    $result = \oci_set_prefetch($statement, $rows);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}


/**
 * Returns a keyword identifying the type of the
 * OCI8 statement.
 *
 * @param resource $statement A valid OCI8 statement identifier from oci_parse.
 * @return string Returns the type of statement as one of the
 * following strings.
 *
 * Statement type
 *
 *
 *
 * Return String
 * Notes
 *
 *
 *
 *
 * ALTER
 *
 *
 *
 * BEGIN
 *
 *
 *
 * CALL
 * Introduced in PHP 5.2.1 (PECL OCI8 1.2.3)
 *
 *
 * CREATE
 *
 *
 *
 * DECLARE
 *
 *
 *
 * DELETE
 *
 *
 *
 * DROP
 *
 *
 *
 * INSERT
 *
 *
 *
 * SELECT
 *
 *
 *
 * UPDATE
 *
 *
 *
 * UNKNOW.
 * @throws Oci8Exception
 *
 */
function oci_statement_type($statement): string
{
    error_clear_last();
    $result = \oci_statement_type($statement);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
    return $result;
}


/**
 * Unregister the user-defined callback function registered to connection
 * by oci_register_taf_callback. See
 * OCI8 Transparent Application Failover (TAF) Support
 * for information.
 *
 * @param resource $connection An Oracle connection identifier.
 * @throws Oci8Exception
 *
 */
function oci_unregister_taf_callback($connection): void
{
    error_clear_last();
    $result = \oci_unregister_taf_callback($connection);
    if ($result === false) {
        throw Oci8Exception::createFromPhpError();
    }
}
