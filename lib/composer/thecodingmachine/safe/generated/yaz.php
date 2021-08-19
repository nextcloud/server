<?php

namespace Safe;

use Safe\Exceptions\YazException;

/**
 * This function invokes a CCL parser. It converts a given CCL FIND query to
 * an RPN query which may be passed to the yaz_search
 * function to perform a search.
 *
 * To define a set of valid CCL fields call yaz_ccl_conf
 * prior to this function.
 *
 * @param resource $id The connection resource returned by yaz_connect.
 * @param string $query The CCL FIND query.
 * @param array|null $result If the function was executed successfully, this will be an array
 * containing the valid RPN query under the key rpn.
 *
 * Upon failure, three indexes are set in this array to indicate the cause
 * of failure:
 *
 *
 *
 * errorcode - the CCL error code (integer)
 *
 *
 *
 *
 * errorstring - the CCL error string
 *
 *
 *
 *
 * errorpos - approximate position in query of failure
 * (integer is character position)
 *
 *
 *
 *
 * errorcode - the CCL error code (integer)
 *
 * errorstring - the CCL error string
 *
 * errorpos - approximate position in query of failure
 * (integer is character position)
 * @throws YazException
 *
 */
function yaz_ccl_parse($id, string $query, ?array &$result): void
{
    error_clear_last();
    $result = \yaz_ccl_parse($id, $query, $result);
    if ($result === false) {
        throw YazException::createFromPhpError();
    }
}


/**
 * Closes the connection given by parameter id.
 *
 * @param resource $id The connection resource returned by yaz_connect.
 * @throws YazException
 *
 */
function yaz_close($id): void
{
    error_clear_last();
    $result = \yaz_close($id);
    if ($result === false) {
        throw YazException::createFromPhpError();
    }
}


/**
 * This function returns a connection resource on success, zero on
 * failure.
 *
 * yaz_connect prepares for a connection to a
 * Z39.50 server.
 * This function is non-blocking and does not attempt to establish
 * a connection  - it merely prepares a connect to be performed later when
 * yaz_wait is called.
 *
 * @param string $zurl A string that takes the form host[:port][/database].
 * If port is omitted, port 210 is used. If database is omitted
 * Default is used.
 * @param mixed $options If given as a string, it is treated as the Z39.50 V2 authentication
 * string (OpenAuth).
 *
 * If given as an array, the contents of the array serves as options.
 *
 *
 * user
 *
 *
 * Username for authentication.
 *
 *
 *
 *
 * group
 *
 *
 * Group for authentication.
 *
 *
 *
 *
 * password
 *
 *
 * Password for authentication.
 *
 *
 *
 *
 * cookie
 *
 *
 * Cookie for session (YAZ proxy).
 *
 *
 *
 *
 * proxy
 *
 *
 * Proxy for connection (YAZ proxy).
 *
 *
 *
 *
 * persistent
 *
 *
 * A boolean. If TRUE the connection is persistent; If FALSE the
 * connection is not persistent. By default connections are persistent.
 *
 *
 *
 * If you open a persistent connection, you won't be able to close
 * it later with yaz_close.
 *
 *
 *
 *
 *
 * piggyback
 *
 *
 * A boolean. If TRUE piggyback is enabled for searches; If FALSE
 * piggyback is disabled. By default piggyback is enabled.
 *
 *
 * Enabling piggyback is more efficient and usually saves a
 * network-round-trip for first time fetches of records. However, a
 * few Z39.50 servers do not support piggyback or they ignore element
 * set names. For those, piggyback should be disabled.
 *
 *
 *
 *
 * charset
 *
 *
 * A string that specifies character set to be used in Z39.50
 * language and character set negotiation. Use strings such as:
 * ISO-8859-1, UTF-8,
 * UTF-16.
 *
 *
 * Most Z39.50 servers do not support this feature (and thus, this is
 * ignored). Many servers use the ISO-8859-1 encoding for queries and
 * messages. MARC21/USMARC records are not affected by this setting.
 *
 *
 *
 *
 *
 * preferredMessageSize
 *
 *
 * An integer that specifies the maximum byte size of all records
 * to be returned by a target during retrieval. See the
 * Z39.50 standard for more
 * information.
 *
 *
 *
 * This option is supported in PECL YAZ 1.0.5 or later.
 *
 *
 *
 *
 *
 *
 * maximumRecordSize
 *
 *
 * An integer that specifies the maximum byte size of a single record
 * to be returned by a target during retrieval. This
 * entity is referred to as Exceptional-record-size in the
 * Z39.50 standard.
 *
 *
 *
 * This option is supported in PECL YAZ 1.0.5 or later.
 *
 *
 *
 *
 *
 *
 *
 * Username for authentication.
 *
 * Group for authentication.
 *
 * Password for authentication.
 *
 * Cookie for session (YAZ proxy).
 *
 * Proxy for connection (YAZ proxy).
 *
 * A boolean. If TRUE the connection is persistent; If FALSE the
 * connection is not persistent. By default connections are persistent.
 *
 * If you open a persistent connection, you won't be able to close
 * it later with yaz_close.
 *
 * A boolean. If TRUE piggyback is enabled for searches; If FALSE
 * piggyback is disabled. By default piggyback is enabled.
 *
 * Enabling piggyback is more efficient and usually saves a
 * network-round-trip for first time fetches of records. However, a
 * few Z39.50 servers do not support piggyback or they ignore element
 * set names. For those, piggyback should be disabled.
 *
 * A string that specifies character set to be used in Z39.50
 * language and character set negotiation. Use strings such as:
 * ISO-8859-1, UTF-8,
 * UTF-16.
 *
 * Most Z39.50 servers do not support this feature (and thus, this is
 * ignored). Many servers use the ISO-8859-1 encoding for queries and
 * messages. MARC21/USMARC records are not affected by this setting.
 *
 * An integer that specifies the maximum byte size of all records
 * to be returned by a target during retrieval. See the
 * Z39.50 standard for more
 * information.
 *
 * This option is supported in PECL YAZ 1.0.5 or later.
 *
 * An integer that specifies the maximum byte size of a single record
 * to be returned by a target during retrieval. This
 * entity is referred to as Exceptional-record-size in the
 * Z39.50 standard.
 *
 * This option is supported in PECL YAZ 1.0.5 or later.
 * @return mixed A connection resource on success, FALSE on error.
 * @throws YazException
 *
 */
function yaz_connect(string $zurl, $options = null)
{
    error_clear_last();
    if ($options !== null) {
        $result = \yaz_connect($zurl, $options);
    } else {
        $result = \yaz_connect($zurl);
    }
    if ($result === false) {
        throw YazException::createFromPhpError();
    }
    return $result;
}


/**
 * This function allows you to change databases within a session by
 * specifying one or more databases to be used in search, retrieval, etc.
 * - overriding databases specified in call to
 * yaz_connect.
 *
 * @param resource $id The connection resource returned by yaz_connect.
 * @param string $databases A string containing one or more databases. Multiple databases are
 * separated by a plus sign +.
 * @throws YazException
 *
 */
function yaz_database($id, string $databases): void
{
    error_clear_last();
    $result = \yaz_database($id, $databases);
    if ($result === false) {
        throw YazException::createFromPhpError();
    }
}


/**
 * This function sets the element set name for retrieval.
 *
 * Call this function before yaz_search or
 * yaz_present to specify the element set name for
 * records to be retrieved.
 *
 * @param resource $id The connection resource returned by yaz_connect.
 * @param string $elementset Most servers support F (for full records) and
 * B (for brief records).
 * @throws YazException
 *
 */
function yaz_element($id, string $elementset): void
{
    error_clear_last();
    $result = \yaz_element($id, $elementset);
    if ($result === false) {
        throw YazException::createFromPhpError();
    }
}


/**
 * This function prepares for retrieval of records after a successful search.
 *
 * The yaz_range function should be called prior to this
 * function to specify the range of records to be retrieved.
 *
 * @param resource $id The connection resource returned by yaz_connect.
 * @throws YazException
 *
 */
function yaz_present($id): void
{
    error_clear_last();
    $result = \yaz_present($id);
    if ($result === false) {
        throw YazException::createFromPhpError();
    }
}


/**
 * yaz_search prepares for a search on the given
 * connection.
 *
 * Like yaz_connect this function is non-blocking and
 * only prepares for a search to be executed later when
 * yaz_wait is called.
 *
 * @param resource $id The connection resource returned by yaz_connect.
 * @param string $type This parameter represents the query type - only "rpn"
 * is supported now in which case the third argument specifies a Type-1
 * query in prefix query notation.
 * @param string $query The RPN query is a textual representation of the Type-1 query as
 * defined by the Z39.50 standard. However, in the text representation
 * as used by YAZ a prefix notation is used, that is the operator
 * precedes the operands. The query string is a sequence of tokens where
 * white space is ignored unless surrounded by double quotes. Tokens beginning
 * with an at-character (@) are considered operators,
 * otherwise they are treated as search terms.
 *
 * You can find information about attributes at the
 * Z39.50 Maintenance Agency
 * site.
 *
 * If you would like to use a more friendly notation,
 * use the CCL parser - functions yaz_ccl_conf and
 * yaz_ccl_parse.
 * @throws YazException
 *
 */
function yaz_search($id, string $type, string $query): void
{
    error_clear_last();
    $result = \yaz_search($id, $type, $query);
    if ($result === false) {
        throw YazException::createFromPhpError();
    }
}


/**
 * This function carries out networked (blocked) activity for outstanding
 * requests which have been prepared by the functions
 * yaz_connect, yaz_search,
 * yaz_present, yaz_scan and
 * yaz_itemorder.
 *
 * yaz_wait returns when all servers have either
 * completed all requests or aborted (in case of errors).
 *
 * @param array $options An associative array of options:
 *
 *
 * timeout
 *
 *
 * Sets timeout in seconds. If a server has not responded within the
 * timeout it is considered dead and yaz_wait
 * returns. The default value for timeout is 15 seconds.
 *
 *
 *
 *
 * event
 *
 *
 * A boolean.
 *
 *
 *
 *
 *
 * Sets timeout in seconds. If a server has not responded within the
 * timeout it is considered dead and yaz_wait
 * returns. The default value for timeout is 15 seconds.
 *
 * A boolean.
 * @return mixed Returns TRUE on success.
 * In event mode, returns resource.
 * @throws YazException
 *
 */
function yaz_wait(array &$options = null)
{
    error_clear_last();
    $result = \yaz_wait($options);
    if ($result === false) {
        throw YazException::createFromPhpError();
    }
    return $result;
}
