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

class LDAP {
	protected $curFunc = '';
	protected $curArgs = array();

	//Simple wrapper for the ldap functions
	public function __call($name, $arguments) {
		$func = 'ldap_' . $name;
		if(function_exists($func)) {
			$this->preFunctionCall($func, $arguments);
			$result = call_user_func_array($func, $arguments);
			$this->postFunctionCall();
			return $result;
		}
	}

	public function control_paged_result_response($linkResource, $resultResource, &$cookie) {
		$this->preFunctionCall('ldap_control_paged_result_response',
			array($linkResource, $resultResource, $cookie));
		$result = ldap_control_paged_result_response(
			$linkResource, $resultResource, $cookie);
		$this->postFunctionCall();

		return $result;
	}

	public function areLDAPFunctionsAvailable() {
		return function_exists('ldap_connect');
	}

	public function hasPagedResultSupport() {
		$hasSupport = function_exists('ldap_control_paged_result')
			&& function_exists('ldap_control_paged_result_response');
		return $hasSupport;
	}

	private function preFunctionCall($functionName, $args) {
		$this->curFunc = $functionName;
		$this->curArgs = $args;
	}

	private function postFunctionCall() {
		if(is_resource($this->curArgs[0])) {
			$errorCode = ldap_errno($this->curArgs[0]);
			$errorMsg  = ldap_error($this->curArgs[0]);
			if($errorCode !== 0) {
				if($this->curFunc === 'ldap_sort' && $errorCode === -4) {
					//You can safely ignore that decoding error.
					//… says https://bugs.php.net/bug.php?id=18023
				} else if($this->curFunc === 'ldap_get_entries' && $errorCode === -4) {
				} else if ($errorCode === 32) {
					//for now
				} else {
					throw new \Exception('LDAP error '.$errorMsg.' (' .$errorCode.') after calling '.$this->curFunc.' with arguments '.print_r($this->curArgs, true));
				}
			}
		}

		$this->curFunc = '';
		$this->curArgs = array();
	}
}