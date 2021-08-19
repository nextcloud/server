<?php

namespace Safe;

use Safe\Exceptions\LdapException;

/**
 * Does the same thing as ldap_add but returns the LDAP result resource to be parsed with ldap_parse_result.
 *
 * @param resource $link_identifier
 * @param string $dn
 * @param array $entry
 * @param array $serverctrls
 * @return resource Returns an LDAP result identifier.
 * @throws LdapException
 *
 */
function ldap_add_ext($link_identifier, string $dn, array $entry, array $serverctrls = null)
{
    error_clear_last();
    $result = \ldap_add_ext($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Add entries in the LDAP directory.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $dn The distinguished name of an LDAP entity.
 * @param array $entry An array that specifies the information about the entry. The values in
 * the entries are indexed by individual attributes.
 * In case of multiple values for an attribute, they are indexed using
 * integers starting with 0.
 *
 *
 *
 * ]]>
 *
 *
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @throws LdapException
 *
 */
function ldap_add($link_identifier, string $dn, array $entry, array $serverctrls = null): void
{
    error_clear_last();
    $result = \ldap_add($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Does the same thing as ldap_bind but returns the LDAP result resource to be parsed with ldap_parse_result.
 *
 * @param resource $link_identifier
 * @param string|null $bind_rdn
 * @param string|null $bind_password
 * @param array $serverctrls
 * @return resource Returns an LDAP result identifier.
 * @throws LdapException
 *
 */
function ldap_bind_ext($link_identifier, ?string $bind_rdn = null, ?string $bind_password = null, array $serverctrls = null)
{
    error_clear_last();
    $result = \ldap_bind_ext($link_identifier, $bind_rdn, $bind_password, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Binds to the LDAP directory with specified RDN and password.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string|null $bind_rdn
 * @param string|null $bind_password
 * @throws LdapException
 *
 */
function ldap_bind($link_identifier, ?string $bind_rdn = null, ?string $bind_password = null): void
{
    error_clear_last();
    $result = \ldap_bind($link_identifier, $bind_rdn, $bind_password);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Retrieve the pagination information send by the server.
 *
 * @param resource $link An LDAP link identifier, returned by ldap_connect.
 * @param resource $result
 * @param string|null $cookie An opaque structure sent by the server.
 * @param int|null $estimated The estimated number of entries to retrieve.
 * @throws LdapException
 *
 */
function ldap_control_paged_result_response($link, $result, ?string &$cookie = null, ?int &$estimated = null): void
{
    error_clear_last();
    $result = \ldap_control_paged_result_response($link, $result, $cookie, $estimated);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Enable LDAP pagination by sending the pagination control (page size, cookie...).
 *
 * @param resource $link An LDAP link identifier, returned by ldap_connect.
 * @param int $pagesize The number of entries by page.
 * @param bool $iscritical Indicates whether the pagination is critical or not.
 * If true and if the server doesn't support pagination, the search
 * will return no result.
 * @param string $cookie An opaque structure sent by the server
 * (ldap_control_paged_result_response).
 * @throws LdapException
 *
 */
function ldap_control_paged_result($link, int $pagesize, bool $iscritical = false, string $cookie = ""): void
{
    error_clear_last();
    $result = \ldap_control_paged_result($link, $pagesize, $iscritical, $cookie);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Returns the number of entries stored in the result of previous search
 * operations.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_identifier The internal LDAP result.
 * @return int Returns number of entries in the result.
 * @throws LdapException
 *
 */
function ldap_count_entries($link_identifier, $result_identifier): int
{
    error_clear_last();
    $result = \ldap_count_entries($link_identifier, $result_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Does the same thing as ldap_delete but returns the LDAP result resource to be parsed with ldap_parse_result.
 *
 * @param resource $link_identifier
 * @param string $dn
 * @param array $serverctrls
 * @return resource Returns an LDAP result identifier.
 * @throws LdapException
 *
 */
function ldap_delete_ext($link_identifier, string $dn, array $serverctrls = null)
{
    error_clear_last();
    $result = \ldap_delete_ext($link_identifier, $dn, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Deletes a particular entry in LDAP directory.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $dn The distinguished name of an LDAP entity.
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @throws LdapException
 *
 */
function ldap_delete($link_identifier, string $dn, array $serverctrls = null): void
{
    error_clear_last();
    $result = \ldap_delete($link_identifier, $dn, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Performs a PASSWD extended operation.
 *
 * @param resource $link An LDAP link identifier, returned by ldap_connect.
 * @param string $user dn of the user to change the password of.
 * @param string $oldpw The old password of this user. May be ommited depending of server configuration.
 * @param string $newpw The new password for this user. May be omitted or empty to have a generated password.
 * @param array $serverctrls If provided, a password policy request control is send with the request and this is
 * filled with an array of LDAP Controls
 * returned with the request.
 * @return mixed Returns the generated password if newpw is empty or omitted.
 * Otherwise returns TRUE on success.
 * @throws LdapException
 *
 */
function ldap_exop_passwd($link, string $user = "", string $oldpw = "", string $newpw = "", array &$serverctrls = null)
{
    error_clear_last();
    $result = \ldap_exop_passwd($link, $user, $oldpw, $newpw, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Performs a WHOAMI extended operation and returns the data.
 *
 * @param resource $link An LDAP link identifier, returned by ldap_connect.
 * @return string The data returned by the server.
 * @throws LdapException
 *
 */
function ldap_exop_whoami($link): string
{
    error_clear_last();
    $result = \ldap_exop_whoami($link);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Performs an extended operation on the specified link with
 * reqoid the OID of the operation and
 * reqdata the data.
 *
 * @param resource $link An LDAP link identifier, returned by ldap_connect.
 * @param string $reqoid The extended operation request OID. You may use one of LDAP_EXOP_START_TLS, LDAP_EXOP_MODIFY_PASSWD, LDAP_EXOP_REFRESH, LDAP_EXOP_WHO_AM_I, LDAP_EXOP_TURN, or a string with the OID of the operation you want to send.
 * @param string $reqdata The extended operation request data. May be NULL for some operations like LDAP_EXOP_WHO_AM_I, may also need to be BER encoded.
 * @param array|null $serverctrls Array of LDAP Controls to send with the request.
 * @param string|null $retdata Will be filled with the extended operation response data if provided.
 * If not provided you may use ldap_parse_exop on the result object
 * later to get this data.
 * @param string|null $retoid Will be filled with the response OID if provided, usually equal to the request OID.
 * @return mixed When used with retdata, returns TRUE on success.
 * When used without retdata, returns a result identifier.
 * @throws LdapException
 *
 */
function ldap_exop($link, string $reqoid, string $reqdata = null, ?array $serverctrls = null, ?string &$retdata = null, ?string &$retoid = null)
{
    error_clear_last();
    $result = \ldap_exop($link, $reqoid, $reqdata, $serverctrls, $retdata, $retoid);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Splits the DN returned by ldap_get_dn and breaks it
 * up into its component parts. Each part is known as Relative Distinguished
 * Name, or RDN.
 *
 * @param string $dn The distinguished name of an LDAP entity.
 * @param int $with_attrib Used to request if the RDNs are returned with only values or their
 * attributes as well.  To get RDNs with the attributes (i.e. in
 * attribute=value format) set with_attrib to 0
 * and to get only values set it to 1.
 * @return array Returns an array of all DN components.
 * The first element in the array has count key and
 * represents the number of returned values, next elements are numerically
 * indexed DN components.
 * @throws LdapException
 *
 */
function ldap_explode_dn(string $dn, int $with_attrib): array
{
    error_clear_last();
    $result = \ldap_explode_dn($dn, $with_attrib);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Gets the first attribute in the given entry. Remaining attributes are
 * retrieved by calling ldap_next_attribute successively.
 *
 * Similar to reading entries, attributes are also read one by one from a
 * particular entry.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_entry_identifier
 * @return string Returns the first attribute in the entry on success and FALSE on
 * error.
 * @throws LdapException
 *
 */
function ldap_first_attribute($link_identifier, $result_entry_identifier): string
{
    error_clear_last();
    $result = \ldap_first_attribute($link_identifier, $result_entry_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the entry identifier for first entry in the result. This entry
 * identifier is then supplied to ldap_next_entry
 * routine to get successive entries from the result.
 *
 * Entries in the LDAP result are read sequentially using the
 * ldap_first_entry and
 * ldap_next_entry functions.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_identifier
 * @return resource Returns the result entry identifier for the first entry on success and
 * FALSE on error.
 * @throws LdapException
 *
 */
function ldap_first_entry($link_identifier, $result_identifier)
{
    error_clear_last();
    $result = \ldap_first_entry($link_identifier, $result_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Frees up the memory allocated internally to store the result. All result
 * memory will be automatically freed when the script terminates.
 *
 * Typically all the memory allocated for the LDAP result gets freed at the
 * end of the script. In case the script is making successive searches which
 * return large result sets, ldap_free_result could be
 * called to keep the runtime memory usage by the script low.
 *
 * @param resource $result_identifier
 * @throws LdapException
 *
 */
function ldap_free_result($result_identifier): void
{
    error_clear_last();
    $result = \ldap_free_result($result_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Reads attributes and values from an entry in the search result.
 *
 * Having located a specific entry in the directory, you can find out what
 * information is held for that entry by using this call. You would use this
 * call for an application which "browses" directory entries and/or where you
 * do not know the structure of the directory entries. In many applications
 * you will be searching for a specific attribute such as an email address or
 * a surname, and won't care what other data is held.
 *
 *
 *
 *
 *
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_entry_identifier
 * @return array Returns a complete entry information in a multi-dimensional array
 * on success and FALSE on error.
 * @throws LdapException
 *
 */
function ldap_get_attributes($link_identifier, $result_entry_identifier): array
{
    error_clear_last();
    $result = \ldap_get_attributes($link_identifier, $result_entry_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Finds out the DN of an entry in the result.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_entry_identifier
 * @return string Returns the DN of the result entry and FALSE on error.
 * @throws LdapException
 *
 */
function ldap_get_dn($link_identifier, $result_entry_identifier): string
{
    error_clear_last();
    $result = \ldap_get_dn($link_identifier, $result_entry_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Reads multiple entries from the given result, and then reading the
 * attributes and multiple values.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_identifier
 * @return array Returns a complete result information in a multi-dimensional array on
 * success and FALSE on error.
 *
 * The structure of the array is as follows.
 * The attribute index is converted to lowercase. (Attributes are
 * case-insensitive for directory servers, but not when used as
 * array indices.)
 *
 *
 *
 *
 *
 * @throws LdapException
 *
 */
function ldap_get_entries($link_identifier, $result_identifier): array
{
    error_clear_last();
    $result = \ldap_get_entries($link_identifier, $result_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets retval to the value of the specified option.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param int $option The parameter option can be one of:
 *
 *
 *
 *
 * Option
 * Type
 * since
 *
 *
 *
 *
 * LDAP_OPT_DEREF
 * integer
 *
 *
 *
 * LDAP_OPT_SIZELIMIT
 * integer
 *
 *
 *
 * LDAP_OPT_TIMELIMIT
 * integer
 *
 *
 *
 * LDAP_OPT_NETWORK_TIMEOUT
 * integer
 *
 *
 *
 * LDAP_OPT_PROTOCOL_VERSION
 * integer
 *
 *
 *
 * LDAP_OPT_ERROR_NUMBER
 * integer
 *
 *
 *
 * LDAP_OPT_DIAGNOSTIC_MESSAGE
 * integer
 *
 *
 *
 * LDAP_OPT_REFERRALS
 * bool
 *
 *
 *
 * LDAP_OPT_RESTART
 * bool
 *
 *
 *
 * LDAP_OPT_HOST_NAME
 * string
 *
 *
 *
 * LDAP_OPT_ERROR_STRING
 * string
 *
 *
 *
 * LDAP_OPT_MATCHED_DN
 * string
 *
 *
 *
 * LDAP_OPT_SERVER_CONTROLS
 * array
 *
 *
 *
 * LDAP_OPT_CLIENT_CONTROLS
 * array
 *
 *
 *
 * LDAP_OPT_X_KEEPALIVE_IDLE
 * int
 * 7.1
 *
 *
 * LDAP_OPT_X_KEEPALIVE_PROBES
 * int
 * 7.1
 *
 *
 * LDAP_OPT_X_KEEPALIVE_INTERVAL
 * int
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CACERTDIR
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CACERTFILE
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CERTFILE
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CIPHER_SUITE
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CRLCHECK
 * integer
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CRL_NONE
 * integer
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CRL_PEER
 * integer
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CRL_ALL
 * integer
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_CRLFILE
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_DHFILE
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_KEYILE
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_PACKAGE
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_PROTOCOL_MIN
 * integer
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_RANDOM_FILE
 * string
 * 7.1
 *
 *
 * LDAP_OPT_X_TLS_REQUIRE_CERT
 * integer
 *
 *
 *
 *
 *
 * @param mixed $retval This will be set to the option value.
 * @throws LdapException
 *
 */
function ldap_get_option($link_identifier, int $option, &$retval): void
{
    error_clear_last();
    $result = \ldap_get_option($link_identifier, $option, $retval);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Reads all the values of the attribute in the entry in the result.
 *
 * This function is used exactly like ldap_get_values
 * except that it handles binary data and not string data.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_entry_identifier
 * @param string $attribute
 * @return array Returns an array of values for the attribute on success and FALSE on
 * error. Individual values are accessed by integer index in the array. The
 * first index is 0. The number of values can be found by indexing "count"
 * in the resultant array.
 * @throws LdapException
 *
 */
function ldap_get_values_len($link_identifier, $result_entry_identifier, string $attribute): array
{
    error_clear_last();
    $result = \ldap_get_values_len($link_identifier, $result_entry_identifier, $attribute);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Reads all the values of the attribute in the entry in the result.
 *
 * This call needs a result_entry_identifier,
 * so needs to be preceded by one of the ldap search calls and one
 * of the calls to get an individual entry.
 *
 * You application will either be hard coded to look for certain
 * attributes (such as "surname" or "mail") or you will have to use
 * the ldap_get_attributes call to work out
 * what attributes exist for a given entry.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_entry_identifier
 * @param string $attribute
 * @return array Returns an array of values for the attribute on success and FALSE on
 * error. The number of values can be found by indexing "count" in the
 * resultant array. Individual values are accessed by integer index in the
 * array.  The first index is 0.
 *
 * LDAP allows more than one entry for an attribute, so it can, for example,
 * store a number of email addresses for one person's directory entry all
 * labeled with the attribute "mail"
 *
 *
 * return_value["count"] = number of values for attribute
 * return_value[0] = first value of attribute
 * return_value[i] = ith value of attribute
 *
 *
 * @throws LdapException
 *
 */
function ldap_get_values($link_identifier, $result_entry_identifier, string $attribute): array
{
    error_clear_last();
    $result = \ldap_get_values($link_identifier, $result_entry_identifier, $attribute);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Performs the search for a specified filter on the
 * directory with the scope LDAP_SCOPE_ONELEVEL.
 *
 * LDAP_SCOPE_ONELEVEL means that the search should only
 * return information that is at the level immediately below the
 * base_dn given in the call.
 * (Equivalent to typing "ls" and getting a list of files and folders in the
 * current working directory.)
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $base_dn The base DN for the directory.
 * @param string $filter
 * @param array $attributes An array of the required attributes, e.g. array("mail", "sn", "cn").
 * Note that the "dn" is always returned irrespective of which attributes
 * types are requested.
 *
 * Using this parameter is much more efficient than the default action
 * (which is to return all attributes and their associated values).
 * The use of this parameter should therefore be considered good
 * practice.
 * @param int $attrsonly Should be set to 1 if only attribute types are wanted. If set to 0
 * both attributes types and attribute values are fetched which is the
 * default behaviour.
 * @param int $sizelimit Enables you to limit the count of entries fetched. Setting this to 0
 * means no limit.
 *
 * This parameter can NOT override server-side preset sizelimit. You can
 * set it lower though.
 *
 * Some directory server hosts will be configured to return no more than
 * a preset number of entries. If this occurs, the server will indicate
 * that it has only returned a partial results set. This also occurs if
 * you use this parameter to limit the count of fetched entries.
 * @param int $timelimit Sets the number of seconds how long is spend on the search. Setting
 * this to 0 means no limit.
 *
 * This parameter can NOT override server-side preset timelimit. You can
 * set it lower though.
 * @param int $deref Specifies how aliases should be handled during the search. It can be
 * one of the following:
 *
 *
 *
 * LDAP_DEREF_NEVER - (default) aliases are never
 * dereferenced.
 *
 *
 *
 *
 * LDAP_DEREF_SEARCHING - aliases should be
 * dereferenced during the search but not when locating the base object
 * of the search.
 *
 *
 *
 *
 * LDAP_DEREF_FINDING - aliases should be
 * dereferenced when locating the base object but not during the search.
 *
 *
 *
 *
 * LDAP_DEREF_ALWAYS - aliases should be dereferenced
 * always.
 *
 *
 *
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @return resource Returns a search result identifier.
 * @throws LdapException
 *
 */
function ldap_list($link_identifier, string $base_dn, string $filter, array $attributes = null, int $attrsonly = 0, int $sizelimit = -1, int $timelimit = -1, int $deref = LDAP_DEREF_NEVER, array $serverctrls = null)
{
    error_clear_last();
    if ($serverctrls !== null) {
        $result = \ldap_list($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref, $serverctrls);
    } elseif ($deref !== LDAP_DEREF_NEVER) {
        $result = \ldap_list($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
    } elseif ($timelimit !== -1) {
        $result = \ldap_list($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit);
    } elseif ($sizelimit !== -1) {
        $result = \ldap_list($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit);
    } elseif ($attrsonly !== 0) {
        $result = \ldap_list($link_identifier, $base_dn, $filter, $attributes, $attrsonly);
    } elseif ($attributes !== null) {
        $result = \ldap_list($link_identifier, $base_dn, $filter, $attributes);
    } else {
        $result = \ldap_list($link_identifier, $base_dn, $filter);
    }
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Does the same thing as ldap_mod_add but returns the LDAP result resource to be parsed with ldap_parse_result.
 *
 * @param resource $link_identifier
 * @param string $dn
 * @param array $entry
 * @param array $serverctrls
 * @return resource Returns an LDAP result identifier.
 * @throws LdapException
 *
 */
function ldap_mod_add_ext($link_identifier, string $dn, array $entry, array $serverctrls = null)
{
    error_clear_last();
    $result = \ldap_mod_add_ext($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Adds one or more attribute values to the specified dn.
 * To add a whole new object see ldap_add function.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $dn The distinguished name of an LDAP entity.
 * @param array $entry An associative array listing the attirbute values to add. If an attribute was not existing yet it will be added. If an attribute is existing you can only add values to it if it supports multiple values.
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @throws LdapException
 *
 */
function ldap_mod_add($link_identifier, string $dn, array $entry, array $serverctrls = null): void
{
    error_clear_last();
    $result = \ldap_mod_add($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Does the same thing as ldap_mod_del but returns the LDAP result resource to be parsed with ldap_parse_result.
 *
 * @param resource $link_identifier
 * @param string $dn
 * @param array $entry
 * @param array $serverctrls
 * @return resource Returns an LDAP result identifier.
 * @throws LdapException
 *
 */
function ldap_mod_del_ext($link_identifier, string $dn, array $entry, array $serverctrls = null)
{
    error_clear_last();
    $result = \ldap_mod_del_ext($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Removes one or more attribute values from the specified dn.
 * Object deletions are done by the
 * ldap_delete function.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $dn The distinguished name of an LDAP entity.
 * @param array $entry
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @throws LdapException
 *
 */
function ldap_mod_del($link_identifier, string $dn, array $entry, array $serverctrls = null): void
{
    error_clear_last();
    $result = \ldap_mod_del($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Does the same thing as ldap_mod_replace but returns the LDAP result resource to be parsed with ldap_parse_result.
 *
 * @param resource $link_identifier
 * @param string $dn
 * @param array $entry
 * @param array $serverctrls
 * @return resource Returns an LDAP result identifier.
 * @throws LdapException
 *
 */
function ldap_mod_replace_ext($link_identifier, string $dn, array $entry, array $serverctrls = null)
{
    error_clear_last();
    $result = \ldap_mod_replace_ext($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Replaces one or more attributes from the specified dn.
 * It may also add or remove attributes.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $dn The distinguished name of an LDAP entity.
 * @param array $entry An associative array listing the attributes to replace. Sending an empty array as value will remove the attribute, while sending an attribute not existing yet on this entry will add it.
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @throws LdapException
 *
 */
function ldap_mod_replace($link_identifier, string $dn, array $entry, array $serverctrls = null): void
{
    error_clear_last();
    $result = \ldap_mod_replace($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Modifies an existing entry in the LDAP directory. Allows detailed
 * specification of the modifications to perform.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $dn The distinguished name of an LDAP entity.
 * @param array $entry An array that specifies the modifications to make. Each entry in this
 * array is an associative array with two or three keys:
 * attrib maps to the name of the attribute to modify,
 * modtype maps to the type of modification to perform,
 * and (depending on the type of modification) values
 * maps to an array of attribute values relevant to the modification.
 *
 * Possible values for modtype include:
 *
 *
 * LDAP_MODIFY_BATCH_ADD
 *
 *
 * Each value specified through values is added (as
 * an additional value) to the attribute named by
 * attrib.
 *
 *
 *
 *
 * LDAP_MODIFY_BATCH_REMOVE
 *
 *
 * Each value specified through values is removed
 * from the attribute named by attrib. Any value of
 * the attribute not contained in the values array
 * will remain untouched.
 *
 *
 *
 *
 * LDAP_MODIFY_BATCH_REMOVE_ALL
 *
 *
 * All values are removed from the attribute named by
 * attrib. A values entry must
 * not be provided.
 *
 *
 *
 *
 * LDAP_MODIFY_BATCH_REPLACE
 *
 *
 * All current values of the attribute named by
 * attrib are replaced with the values specified
 * through values.
 *
 *
 *
 *
 *
 * Each value specified through values is added (as
 * an additional value) to the attribute named by
 * attrib.
 *
 * Each value specified through values is removed
 * from the attribute named by attrib. Any value of
 * the attribute not contained in the values array
 * will remain untouched.
 *
 * All values are removed from the attribute named by
 * attrib. A values entry must
 * not be provided.
 *
 * All current values of the attribute named by
 * attrib are replaced with the values specified
 * through values.
 *
 * Note that any value for attrib must be a string, any
 * value for values must be an array of strings, and
 * any value for modtype must be one of the
 * LDAP_MODIFY_BATCH_* constants listed above.
 * @param array $serverctrls Each value specified through values is added (as
 * an additional value) to the attribute named by
 * attrib.
 * @throws LdapException
 *
 */
function ldap_modify_batch($link_identifier, string $dn, array $entry, array $serverctrls = null): void
{
    error_clear_last();
    $result = \ldap_modify_batch($link_identifier, $dn, $entry, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Retrieves the attributes in an entry. The first call to
 * ldap_next_attribute is made with the
 * result_entry_identifier returned from
 * ldap_first_attribute.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param resource $result_entry_identifier
 * @return string Returns the next attribute in an entry on success and FALSE on
 * error.
 * @throws LdapException
 *
 */
function ldap_next_attribute($link_identifier, $result_entry_identifier): string
{
    error_clear_last();
    $result = \ldap_next_attribute($link_identifier, $result_entry_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Parse LDAP extended operation data from result object result
 *
 * @param resource $link An LDAP link identifier, returned by ldap_connect.
 * @param resource $result An LDAP result resource, returned by ldap_exop.
 * @param string|null $retdata Will be filled by the response data.
 * @param string|null $retoid Will be filled by the response OID.
 * @throws LdapException
 *
 */
function ldap_parse_exop($link, $result, ?string &$retdata = null, ?string &$retoid = null): void
{
    error_clear_last();
    $result = \ldap_parse_exop($link, $result, $retdata, $retoid);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Parses an LDAP search result.
 *
 * @param resource $link An LDAP link identifier, returned by ldap_connect.
 * @param resource $result An LDAP result resource, returned by ldap_list or
 * ldap_search.
 * @param int|null $errcode A reference to a variable that will be set to the LDAP error code in
 * the result, or 0 if no error occurred.
 * @param string|null $matcheddn A reference to a variable that will be set to a matched DN if one was
 * recognised within the request, otherwise it will be set to NULL.
 * @param string|null $errmsg A reference to a variable that will be set to the LDAP error message in
 * the result, or an empty string if no error occurred.
 * @param array|null $referrals A reference to a variable that will be set to an array set
 * to all of the referral strings in the result, or an empty array if no
 * referrals were returned.
 * @param array|null $serverctrls An array of LDAP Controls which have been sent with the response.
 * @throws LdapException
 *
 */
function ldap_parse_result($link, $result, ?int &$errcode, ?string &$matcheddn = null, ?string &$errmsg = null, ?array &$referrals = null, ?array &$serverctrls = null): void
{
    error_clear_last();
    $result = \ldap_parse_result($link, $result, $errcode, $matcheddn, $errmsg, $referrals, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Performs the search for a specified filter on the
 * directory with the scope LDAP_SCOPE_BASE. So it is
 * equivalent to reading an entry from the directory.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $base_dn The base DN for the directory.
 * @param string $filter An empty filter is not allowed. If you want to retrieve absolutely all
 * information for this entry, use a filter of
 * objectClass=*. If you know which entry types are
 * used on the directory server, you might use an appropriate filter such
 * as objectClass=inetOrgPerson.
 * @param array $attributes An array of the required attributes, e.g. array("mail", "sn", "cn").
 * Note that the "dn" is always returned irrespective of which attributes
 * types are requested.
 *
 * Using this parameter is much more efficient than the default action
 * (which is to return all attributes and their associated values).
 * The use of this parameter should therefore be considered good
 * practice.
 * @param int $attrsonly Should be set to 1 if only attribute types are wanted. If set to 0
 * both attributes types and attribute values are fetched which is the
 * default behaviour.
 * @param int $sizelimit Enables you to limit the count of entries fetched. Setting this to 0
 * means no limit.
 *
 * This parameter can NOT override server-side preset sizelimit. You can
 * set it lower though.
 *
 * Some directory server hosts will be configured to return no more than
 * a preset number of entries. If this occurs, the server will indicate
 * that it has only returned a partial results set. This also occurs if
 * you use this parameter to limit the count of fetched entries.
 * @param int $timelimit Sets the number of seconds how long is spend on the search. Setting
 * this to 0 means no limit.
 *
 * This parameter can NOT override server-side preset timelimit. You can
 * set it lower though.
 * @param int $deref Specifies how aliases should be handled during the search. It can be
 * one of the following:
 *
 *
 *
 * LDAP_DEREF_NEVER - (default) aliases are never
 * dereferenced.
 *
 *
 *
 *
 * LDAP_DEREF_SEARCHING - aliases should be
 * dereferenced during the search but not when locating the base object
 * of the search.
 *
 *
 *
 *
 * LDAP_DEREF_FINDING - aliases should be
 * dereferenced when locating the base object but not during the search.
 *
 *
 *
 *
 * LDAP_DEREF_ALWAYS - aliases should be dereferenced
 * always.
 *
 *
 *
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @return resource Returns a search result identifier.
 * @throws LdapException
 *
 */
function ldap_read($link_identifier, string $base_dn, string $filter, array $attributes = null, int $attrsonly = 0, int $sizelimit = -1, int $timelimit = -1, int $deref = LDAP_DEREF_NEVER, array $serverctrls = null)
{
    error_clear_last();
    if ($serverctrls !== null) {
        $result = \ldap_read($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref, $serverctrls);
    } elseif ($deref !== LDAP_DEREF_NEVER) {
        $result = \ldap_read($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
    } elseif ($timelimit !== -1) {
        $result = \ldap_read($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit);
    } elseif ($sizelimit !== -1) {
        $result = \ldap_read($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit);
    } elseif ($attrsonly !== 0) {
        $result = \ldap_read($link_identifier, $base_dn, $filter, $attributes, $attrsonly);
    } elseif ($attributes !== null) {
        $result = \ldap_read($link_identifier, $base_dn, $filter, $attributes);
    } else {
        $result = \ldap_read($link_identifier, $base_dn, $filter);
    }
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Does the same thing as ldap_rename but returns the LDAP result resource to be parsed with ldap_parse_result.
 *
 * @param resource $link_identifier
 * @param string $dn
 * @param string $newrdn
 * @param string $newparent
 * @param bool $deleteoldrdn
 * @param array $serverctrls
 * @return resource Returns an LDAP result identifier.
 * @throws LdapException
 *
 */
function ldap_rename_ext($link_identifier, string $dn, string $newrdn, string $newparent, bool $deleteoldrdn, array $serverctrls = null)
{
    error_clear_last();
    $result = \ldap_rename_ext($link_identifier, $dn, $newrdn, $newparent, $deleteoldrdn, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * The entry specified by dn is renamed/moved.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $dn The distinguished name of an LDAP entity.
 * @param string $newrdn The new RDN.
 * @param string $newparent The new parent/superior entry.
 * @param bool $deleteoldrdn If TRUE the old RDN value(s) is removed, else the old RDN value(s)
 * is retained as non-distinguished values of the entry.
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @throws LdapException
 *
 */
function ldap_rename($link_identifier, string $dn, string $newrdn, string $newparent, bool $deleteoldrdn, array $serverctrls = null): void
{
    error_clear_last();
    $result = \ldap_rename($link_identifier, $dn, $newrdn, $newparent, $deleteoldrdn, $serverctrls);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 *
 *
 * @param resource $link
 * @param string $binddn
 * @param string $password
 * @param string $sasl_mech
 * @param string $sasl_realm
 * @param string $sasl_authc_id
 * @param string $sasl_authz_id
 * @param string $props
 * @throws LdapException
 *
 */
function ldap_sasl_bind($link, string $binddn = null, string $password = null, string $sasl_mech = null, string $sasl_realm = null, string $sasl_authc_id = null, string $sasl_authz_id = null, string $props = null): void
{
    error_clear_last();
    $result = \ldap_sasl_bind($link, $binddn, $password, $sasl_mech, $sasl_realm, $sasl_authc_id, $sasl_authz_id, $props);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Performs the search for a specified filter on the directory with the scope
 * of LDAP_SCOPE_SUBTREE. This is equivalent to searching
 * the entire directory.
 *
 * From 4.0.5 on it's also possible to do parallel searches. To do this
 * you use an array of link identifiers, rather than a single identifier,
 * as the first argument. If you don't want the same base DN and the
 * same filter for all the searches, you can also use an array of base DNs
 * and/or an array of filters. Those arrays must be of the same size as
 * the link identifier array since the first entries of the arrays are
 * used for one search, the second entries are used for another, and so
 * on. When doing parallel searches an array of search result
 * identifiers is returned, except in case of error, then the entry
 * corresponding to the search will be FALSE. This is very much like
 * the value normally returned, except that a result identifier is always
 * returned when a search was made. There are some rare cases where the
 * normal search returns FALSE while the parallel search returns an
 * identifier.
 *
 * @param resource|array $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param string $base_dn The base DN for the directory.
 * @param string $filter The search filter can be simple or advanced, using boolean operators in
 * the format described in the LDAP documentation (see the Netscape Directory SDK or
 * RFC4515 for full
 * information on filters).
 * @param array $attributes An array of the required attributes, e.g. array("mail", "sn", "cn").
 * Note that the "dn" is always returned irrespective of which attributes
 * types are requested.
 *
 * Using this parameter is much more efficient than the default action
 * (which is to return all attributes and their associated values).
 * The use of this parameter should therefore be considered good
 * practice.
 * @param int $attrsonly Should be set to 1 if only attribute types are wanted. If set to 0
 * both attributes types and attribute values are fetched which is the
 * default behaviour.
 * @param int $sizelimit Enables you to limit the count of entries fetched. Setting this to 0
 * means no limit.
 *
 * This parameter can NOT override server-side preset sizelimit. You can
 * set it lower though.
 *
 * Some directory server hosts will be configured to return no more than
 * a preset number of entries. If this occurs, the server will indicate
 * that it has only returned a partial results set. This also occurs if
 * you use this parameter to limit the count of fetched entries.
 * @param int $timelimit Sets the number of seconds how long is spend on the search. Setting
 * this to 0 means no limit.
 *
 * This parameter can NOT override server-side preset timelimit. You can
 * set it lower though.
 * @param int $deref Specifies how aliases should be handled during the search. It can be
 * one of the following:
 *
 *
 *
 * LDAP_DEREF_NEVER - (default) aliases are never
 * dereferenced.
 *
 *
 *
 *
 * LDAP_DEREF_SEARCHING - aliases should be
 * dereferenced during the search but not when locating the base object
 * of the search.
 *
 *
 *
 *
 * LDAP_DEREF_FINDING - aliases should be
 * dereferenced when locating the base object but not during the search.
 *
 *
 *
 *
 * LDAP_DEREF_ALWAYS - aliases should be dereferenced
 * always.
 *
 *
 *
 * @param array $serverctrls Array of LDAP Controls to send with the request.
 * @return resource Returns a search result identifier.
 * @throws LdapException
 *
 */
function ldap_search($link_identifier, string $base_dn, string $filter, array $attributes = null, int $attrsonly = 0, int $sizelimit = -1, int $timelimit = -1, int $deref = LDAP_DEREF_NEVER, array $serverctrls = null)
{
    error_clear_last();
    if ($serverctrls !== null) {
        $result = \ldap_search($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref, $serverctrls);
    } elseif ($deref !== LDAP_DEREF_NEVER) {
        $result = \ldap_search($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
    } elseif ($timelimit !== -1) {
        $result = \ldap_search($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit);
    } elseif ($sizelimit !== -1) {
        $result = \ldap_search($link_identifier, $base_dn, $filter, $attributes, $attrsonly, $sizelimit);
    } elseif ($attrsonly !== 0) {
        $result = \ldap_search($link_identifier, $base_dn, $filter, $attributes, $attrsonly);
    } elseif ($attributes !== null) {
        $result = \ldap_search($link_identifier, $base_dn, $filter, $attributes);
    } else {
        $result = \ldap_search($link_identifier, $base_dn, $filter);
    }
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets the value of the specified option to be newval.
 *
 * @param resource|null $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @param int $option The parameter option can be one of:
 *
 *
 *
 *
 * Option
 * Type
 * Available since
 *
 *
 *
 *
 * LDAP_OPT_DEREF
 * integer
 *
 *
 *
 * LDAP_OPT_SIZELIMIT
 * integer
 *
 *
 *
 * LDAP_OPT_TIMELIMIT
 * integer
 *
 *
 *
 * LDAP_OPT_NETWORK_TIMEOUT
 * integer
 * PHP 5.3.0
 *
 *
 * LDAP_OPT_PROTOCOL_VERSION
 * integer
 *
 *
 *
 * LDAP_OPT_ERROR_NUMBER
 * integer
 *
 *
 *
 * LDAP_OPT_REFERRALS
 * bool
 *
 *
 *
 * LDAP_OPT_RESTART
 * bool
 *
 *
 *
 * LDAP_OPT_HOST_NAME
 * string
 *
 *
 *
 * LDAP_OPT_ERROR_STRING
 * string
 *
 *
 *
 * LDAP_OPT_DIAGNOSTIC_MESSAGE
 * string
 *
 *
 *
 * LDAP_OPT_MATCHED_DN
 * string
 *
 *
 *
 * LDAP_OPT_SERVER_CONTROLS
 * array
 *
 *
 *
 * LDAP_OPT_CLIENT_CONTROLS
 * array
 *
 *
 *
 * LDAP_OPT_X_KEEPALIVE_IDLE
 * int
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_KEEPALIVE_PROBES
 * int
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_KEEPALIVE_INTERVAL
 * int
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_CACERTDIR
 * string
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_CACERTFILE
 * string
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_CERTFILE
 * string
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_CIPHER_SUITE
 * string
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_CRLCHECK
 * integer
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_CRLFILE
 * string
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_DHFILE
 * string
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_KEYFILE
 * string
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_PROTOCOL_MIN
 * integer
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_RANDOM_FILE
 * string
 * PHP 7.1.0
 *
 *
 * LDAP_OPT_X_TLS_REQUIRE_CERT
 * integer
 * PHP 7.0.5
 *
 *
 *
 *
 *
 * LDAP_OPT_SERVER_CONTROLS and
 * LDAP_OPT_CLIENT_CONTROLS require a list of
 * controls, this means that the value must be an array of controls. A
 * control consists of an oid identifying the control,
 * an optional value, and an optional flag for
 * criticality. In PHP a control is given by an
 * array containing an element with the key oid
 * and string value, and two optional elements. The optional
 * elements are key value with string value
 * and key iscritical with boolean value.
 * iscritical defaults to FALSE
 * if not supplied. See draft-ietf-ldapext-ldap-c-api-xx.txt
 * for details. See also the second example below.
 * @param mixed $newval The new value for the specified option.
 * @throws LdapException
 *
 */
function ldap_set_option($link_identifier, int $option, $newval): void
{
    error_clear_last();
    $result = \ldap_set_option($link_identifier, $option, $newval);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}


/**
 * Unbinds from the LDAP directory.
 *
 * @param resource $link_identifier An LDAP link identifier, returned by ldap_connect.
 * @throws LdapException
 *
 */
function ldap_unbind($link_identifier): void
{
    error_clear_last();
    $result = \ldap_unbind($link_identifier);
    if ($result === false) {
        throw LdapException::createFromPhpError();
    }
}
