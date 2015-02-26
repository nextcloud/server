<?php

/**
 * ownCloud – LDAP Wrapper
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

class LDAP implements ILDAPWrapper {
	protected $curFunc = '';
	protected $curArgs = array();

	/**
	 * @param resource $link
	 * @param string $dn
	 * @param string $password
	 * @return bool|mixed
	 */
	public function bind($link, $dn, $password) {
		return $this->invokeLDAPMethod('bind', $link, $dn, $password);
	}

	/**
	 * @param string $host
	 * @param string $port
	 * @return mixed
	 */
	public function connect($host, $port) {
		return $this->invokeLDAPMethod('connect', $host, $port);
	}

	/**
	 * @param LDAP $link
	 * @param LDAP $result
	 * @param string $cookie
	 * @return bool|LDAP
	 */
	public function controlPagedResultResponse($link, $result, &$cookie) {
		$this->preFunctionCall('ldap_control_paged_result_response',
			array($link, $result, $cookie));
		$result = ldap_control_paged_result_response($link, $result, $cookie);
		$this->postFunctionCall();

		return $result;
	}

	/**
	 * @param LDAP $link
	 * @param int $pageSize
	 * @param bool $isCritical
	 * @param string $cookie
	 * @return mixed|true
	 */
	public function controlPagedResult($link, $pageSize, $isCritical, $cookie) {
		return $this->invokeLDAPMethod('control_paged_result', $link, $pageSize,
										$isCritical, $cookie);
	}

	/**
	 * @param LDAP $link
	 * @param LDAP $result
	 * @return mixed
	 */
	public function countEntries($link, $result) {
		return $this->invokeLDAPMethod('count_entries', $link, $result);
	}

	/**
	 * @param LDAP $link
	 * @return mixed|string
	 */
	public function errno($link) {
		return $this->invokeLDAPMethod('errno', $link);
	}

	/**
	 * @param LDAP $link
	 * @return int|mixed
	 */
	public function error($link) {
		return $this->invokeLDAPMethod('error', $link);
	}

	/**
	 * Splits DN into its component parts
	 * @param string $dn
	 * @param int @withAttrib
	 * @return array|false
	 * @link http://www.php.net/manual/en/function.ldap-explode-dn.php
	 */
	public function explodeDN($dn, $withAttrib) {
		return $this->invokeLDAPMethod('explode_dn', $dn, $withAttrib);
	}

	/**
	 * @param LDAP $link
	 * @param LDAP $result
	 * @return mixed
	 */
	public function firstEntry($link, $result) {
		return $this->invokeLDAPMethod('first_entry', $link, $result);
	}

	/**
	 * @param LDAP $link
	 * @param LDAP $result
	 * @return array|mixed
	 */
	public function getAttributes($link, $result) {
		return $this->invokeLDAPMethod('get_attributes', $link, $result);
	}

	/**
	 * @param LDAP $link
	 * @param LDAP $result
	 * @return mixed|string
	 */
	public function getDN($link, $result) {
		return $this->invokeLDAPMethod('get_dn', $link, $result);
	}

	/**
	 * @param LDAP $link
	 * @param LDAP $result
	 * @return array|mixed
	 */
	public function getEntries($link, $result) {
		return $this->invokeLDAPMethod('get_entries', $link, $result);
	}

	/**
	 * @param LDAP $link
	 * @param resource $result
	 * @return mixed|an
	 */
	public function nextEntry($link, $result) {
		return $this->invokeLDAPMethod('next_entry', $link, $result);
	}

	/**
	 * @param LDAP $link
	 * @param string $baseDN
	 * @param string $filter
	 * @param array $attr
	 * @return mixed
	 */
	public function read($link, $baseDN, $filter, $attr) {
		return $this->invokeLDAPMethod('read', $link, $baseDN, $filter, $attr);
	}

	/**
	 * @param LDAP $link
	 * @param string $baseDN
	 * @param string $filter
	 * @param array $attr
	 * @param int $attrsOnly
	 * @param int $limit
	 * @return mixed
	 */
	public function search($link, $baseDN, $filter, $attr, $attrsOnly = 0, $limit = 0) {
		return $this->invokeLDAPMethod('search', $link, $baseDN, $filter, $attr, $attrsOnly, $limit);
	}

	/**
	 * @param LDAP $link
	 * @param string $option
	 * @param int $value
	 * @return bool|mixed
	 */
	public function setOption($link, $option, $value) {
		return $this->invokeLDAPMethod('set_option', $link, $option, $value);
	}

	/**
	 * @param LDAP $link
	 * @param LDAP $result
	 * @param string $sortFilter
	 * @return mixed
	 */
	public function sort($link, $result, $sortFilter) {
		return $this->invokeLDAPMethod('sort', $link, $result, $sortFilter);
	}

	/**
	 * @param LDAP $link
	 * @return mixed|true
	 */
	public function startTls($link) {
		return $this->invokeLDAPMethod('start_tls', $link);
	}

	/**
	 * @param resource $link
	 * @return bool|mixed
	 */
	public function unbind($link) {
		return $this->invokeLDAPMethod('unbind', $link);
	}

	/**
	 * Checks whether the server supports LDAP
	 * @return boolean if it the case, false otherwise
	 * */
	public function areLDAPFunctionsAvailable() {
		return function_exists('ldap_connect');
	}

	/**
	 * Checks whether PHP supports LDAP Paged Results
	 * @return boolean if it the case, false otherwise
	 * */
	public function hasPagedResultSupport() {
		$hasSupport = function_exists('ldap_control_paged_result')
			&& function_exists('ldap_control_paged_result_response');
		return $hasSupport;
	}

	/**
	 * Checks whether the submitted parameter is a resource
	 * @param Resource $resource the resource variable to check
	 * @return bool true if it is a resource, false otherwise
	 */
	public function isResource($resource) {
		return is_resource($resource);
	}

	/**
	 * @return mixed
	 */
	private function invokeLDAPMethod() {
		$arguments = func_get_args();
		$func = 'ldap_' . array_shift($arguments);
		if(function_exists($func)) {
			$this->preFunctionCall($func, $arguments);
			$result = call_user_func_array($func, $arguments);
			if ($result === FALSE) {
				$this->postFunctionCall();
			}
			return $result;
		}
	}

	/**
	 * @param string $functionName
	 * @param array $args
	 */
	private function preFunctionCall($functionName, $args) {
		$this->curFunc = $functionName;
		$this->curArgs = $args;
	}

	private function postFunctionCall() {
		if($this->isResource($this->curArgs[0])) {
			$errorCode = ldap_errno($this->curArgs[0]);
			$errorMsg  = ldap_error($this->curArgs[0]);
			if($errorCode !== 0) {
				if($this->curFunc === 'ldap_sort' && $errorCode === -4) {
					//You can safely ignore that decoding error.
					//… says https://bugs.php.net/bug.php?id=18023
				} else if($this->curFunc === 'ldap_get_entries'
						  && $errorCode === -4) {
				} else if ($errorCode === 32) {
					//for now
				} else if ($errorCode === 10) {
					//referrals, we switch them off, but then there is AD :)
				} else {
					\OCP\Util::writeLog('user_ldap',
										'LDAP error '.$errorMsg.' (' .
											$errorCode.') after calling '.
											$this->curFunc,
										\OCP\Util::DEBUG);
				}
			}
		}

		$this->curFunc = '';
		$this->curArgs = array();
	}
}
