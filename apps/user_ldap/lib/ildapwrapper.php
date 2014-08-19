<?php

/**
 * ownCloud – LDAP Wrapper Interface
 *
 * @author Arthur Schiwon
 * @copyright 2013 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\user_ldap\lib;

interface ILDAPWrapper {

	//LDAP functions in use

	/**
	 * @brief Bind to LDAP directory
	 * @param $link LDAP link resource
	 * @param $dn an RDN to log in with
	 * @param $password the password
	 * @return true on success, false otherwise
	 *
	 * with $dn and $password as null a anonymous bind is attempted.
	 */
	public function bind($link, $dn, $password);

	/**
	 * @brief connect to an LDAP server
	 * @param $host The host to connect to
	 * @param $port The port to connect to
	 * @return a link resource on success, otherwise false
	 */
	public function connect($host, $port);

	/**
	 * @brief Send LDAP pagination control
	 * @param $link LDAP link resource
	 * @param $pagesize number of results per page
	 * @param $isCritical Indicates whether the pagination is critical of not.
	 * @param string $cookie structure sent by LDAP server
	 * @return true on success, false otherwise
	 */
	public function controlPagedResult($link, $pagesize, $isCritical, $cookie);

	/**
	 * @brief Retrieve the LDAP pagination cookie
	 * @param $link LDAP link resource
	 * @param $result LDAP result resource
	 * @param $cookie structure sent by LDAP server
	 * @return true on success, false otherwise
	 *
	 * Corresponds to ldap_control_paged_result_response
	 */
	public function controlPagedResultResponse($link, $result, &$cookie);

	/**
	 * @brief Count the number of entries in a search
	 * @param $link LDAP link resource
	 * @param $result LDAP result resource
	 * @return mixed, number of results on success, false otherwise
	 */
	public function countEntries($link, $result);

	/**
	 * @brief Return the LDAP error number of the last LDAP command
	 * @param $link LDAP link resource
	 * @return error message as string
	 */
	public function errno($link);

	/**
	 * @brief Return the LDAP error message of the last LDAP command
	 * @param $link LDAP link resource
	 * @return error code as integer
	 */
	public function error($link);

	/**
	 * @brief Return first result id
	 * @param $link LDAP link resource
	 * @param $result LDAP result resource
	 * @return an LDAP search result resource
	 * */
	public function firstEntry($link, $result);

	/**
	 * @brief Get attributes from a search result entry
	 * @param $link LDAP link resource
	 * @param $result LDAP result resource
	 * @return array containing the results, false on error
	 * */
	public function getAttributes($link, $result);

	/**
	 * @brief Get the DN of a result entry
	 * @param $link LDAP link resource
	 * @param $result LDAP result resource
	 * @return string containing the DN, false on error
	 */
	public function getDN($link, $result);

	/**
	 * @brief Get all result entries
	 * @param $link LDAP link resource
	 * @param $result LDAP result resource
	 * @return array containing the results, false on error
	 */
	public function getEntries($link, $result);

	/**
	 * @brief Return next result id
	 * @param $link LDAP link resource
	 * @param $result LDAP entry result resource
	 * @return an LDAP search result resource
	 * */
	public function nextEntry($link, $result);

	/**
	 * @brief Read an entry
	 * @param $link LDAP link resource
	 * @param $baseDN The DN of the entry to read from
	 * @param $filter An LDAP filter
	 * @param $attr array of the attributes to read
	 * @return an LDAP search result resource
	 */
	public function read($link, $baseDN, $filter, $attr);

	/**
	 * @brief Search LDAP tree
	 * @param $link LDAP link resource
	 * @param $baseDN The DN of the entry to read from
	 * @param $filter An LDAP filter
	 * @param $attr array of the attributes to read
	 * @param $attrsonly optional, 1 if only attribute types shall be returned
	 * @param $limit optional, limits the result entries
	 * @return an LDAP search result resource, false on error
	 */
	public function search($link, $baseDN, $filter, $attr, $attrsonly = 0, $limit = 0);

	/**
	 * @brief Sets the value of the specified option to be $value
	 * @param $link LDAP link resource
	 * @param $option a defined LDAP Server option
	 * @param $value the new value for the option
	 * @return true on success, false otherwise
	 */
	public function setOption($link, $option, $value);

	/**
	 * @brief establish Start TLS
	 * @param $link LDAP link resource
	 * @return true on success, false otherwise
	 */
	public function startTls($link);

	/**
	 * @brief Sort the result of a LDAP search
	 * @param $link LDAP link resource
	 * @param $result LDAP result resource
	 * @param $sortfilter attribute to use a key in sort
	 */
	public function sort($link, $result, $sortfilter);

	/**
	 * @brief Unbind from LDAP directory
	 * @param $link LDAP link resource
	 * @return true on success, false otherwise
	 */
	public function unbind($link);

	//additional required methods in owncloud

	/**
	 * @brief Checks whether the server supports LDAP
	 * @return true if it the case, false otherwise
	 * */
	public function areLDAPFunctionsAvailable();

	/**
	 * @brief Checks whether PHP supports LDAP Paged Results
	 * @return true if it the case, false otherwise
	 * */
	public function hasPagedResultSupport();

	/**
	 * @brief Checks whether the submitted parameter is a resource
	 * @param $resource the resource variable to check
	 * @return true if it is a resource, false otherwise
	 */
	public function isResource($resource);

}
