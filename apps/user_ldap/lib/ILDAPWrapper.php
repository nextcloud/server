<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

interface ILDAPWrapper {
	//LDAP functions in use

	/**
	 * Bind to LDAP directory
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param string $dn an RDN to log in with
	 * @param string $password the password
	 * @return bool true on success, false otherwise
	 *
	 * with $dn and $password as null a anonymous bind is attempted.
	 */
	public function bind($link, $dn, $password);

	/**
	 * connect to an LDAP server
	 * @param string $host The host to connect to
	 * @param string $port The port to connect to
	 * @return \LDAP\Connection|false a link resource on success, otherwise false
	 */
	public function connect($host, $port);

	/**
	 * Retrieve the LDAP pagination cookie
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param \LDAP\Result $result LDAP result resource
	 * @param string &$cookie structure sent by LDAP server
	 * @return bool true on success, false otherwise
	 *
	 * Corresponds to ldap_control_paged_result_response
	 */
	public function controlPagedResultResponse($link, $result, &$cookie);

	/**
	 * Count the number of entries in a search
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param \LDAP\Result $result LDAP result resource
	 * @return int|false number of results on success, false otherwise
	 */
	public function countEntries($link, $result);

	/**
	 * Return the LDAP error number of the last LDAP command
	 * @param \LDAP\Connection $link LDAP link resource
	 * @return int error code
	 */
	public function errno($link);

	/**
	 * Return the LDAP error message of the last LDAP command
	 * @param \LDAP\Connection $link LDAP link resource
	 * @return string error message
	 */
	public function error($link);

	/**
	 * Splits DN into its component parts
	 * @param string $dn
	 * @param int @withAttrib
	 * @return array|false
	 * @link https://www.php.net/manual/en/function.ldap-explode-dn.php
	 */
	public function explodeDN($dn, $withAttrib);

	/**
	 * Return first result id
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param \LDAP\Result $result LDAP result resource
	 * @return \LDAP\ResultEntry an LDAP entry resource
	 * */
	public function firstEntry($link, $result);

	/**
	 * Get attributes from a search result entry
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param \LDAP\ResultEntry $result LDAP result resource
	 * @return array|false containing the results, false on error
	 * */
	public function getAttributes($link, $result);

	/**
	 * Get the DN of a result entry
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param \LDAP\ResultEntry $result LDAP result resource
	 * @return string|false containing the DN, false on error
	 */
	public function getDN($link, $result);

	/**
	 * Get all result entries
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param \LDAP\Result $result LDAP result resource
	 * @return array|false containing the results, false on error
	 */
	public function getEntries($link, $result);

	/**
	 * Return next result id
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param \LDAP\ResultEntry $result LDAP result resource
	 * @return \LDAP\ResultEntry an LDAP entry resource
	 * */
	public function nextEntry($link, $result);

	/**
	 * Read an entry
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param string $baseDN The DN of the entry to read from
	 * @param string $filter An LDAP filter
	 * @param array $attr array of the attributes to read
	 * @return \LDAP\Result an LDAP search result resource
	 */
	public function read($link, $baseDN, $filter, $attr);

	/**
	 * Search LDAP tree
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param string $baseDN The DN of the entry to read from
	 * @param string $filter An LDAP filter
	 * @param array $attr array of the attributes to read
	 * @param int $attrsOnly optional, 1 if only attribute types shall be returned
	 * @param int $limit optional, limits the result entries
	 * @return \LDAP\Result|false an LDAP search result resource, false on error
	 */
	public function search($link, string $baseDN, string $filter, array $attr, int $attrsOnly = 0, int $limit = 0, int $pageSize = 0, string $cookie = '');

	/**
	 * Replace the value of a userPassword by $password
	 * @param \LDAP\Connection $link LDAP link resource
	 * @param string $userDN the DN of the user whose password is to be replaced
	 * @param string $password the new value for the userPassword
	 * @return bool true on success, false otherwise
	 */
	public function modReplace($link, $userDN, $password);

	/**
	 * Performs a PASSWD extended operation.
	 * @param \LDAP\Connection $link LDAP link resource
	 * @return bool|string The generated password if new_password is empty or omitted. Otherwise true on success and false on failure.
	 */
	public function exopPasswd($link, string $userDN, string $oldPassword, string $password);

	/**
	 * Sets the value of the specified option to be $value
	 * @param ?\LDAP\Connection $link LDAP link resource
	 * @param int $option a defined LDAP Server option
	 * @param mixed $value the new value for the option
	 * @return bool true on success, false otherwise
	 */
	public function setOption($link, $option, $value);

	/**
	 * establish Start TLS
	 * @param \LDAP\Connection $link LDAP link resource
	 * @return bool true on success, false otherwise
	 */
	public function startTls($link);

	/**
	 * Unbind from LDAP directory
	 * @param \LDAP\Connection $link LDAP link resource
	 * @return bool true on success, false otherwise
	 */
	public function unbind($link);

	//additional required methods in Nextcloud

	/**
	 * Checks whether the server supports LDAP
	 * @return bool true if it the case, false otherwise
	 * */
	public function areLDAPFunctionsAvailable();

	/**
	 * Checks whether the submitted parameter is a resource
	 * @param mixed $resource the resource variable to check
	 * @psalm-assert-if-true object $resource
	 * @return bool true if it is a resource or LDAP object, false otherwise
	 */
	public function isResource($resource);
}
