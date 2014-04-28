<?php

/**
 * ownCloud – LDAP Backend Proxy
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

use OCA\user_ldap\lib\Access;

abstract class Proxy {
	static private $accesses = array();
	private $ldap = null;

	public function __construct(ILDAPWrapper $ldap) {
		$this->ldap = $ldap;
		$this->cache = \OC_Cache::getGlobalCache();
	}

	private function addAccess($configPrefix) {
		$connector = new Connection($this->ldap, $configPrefix);
		self::$accesses[$configPrefix] = new Access($connector, $this->ldap);
	}

	protected function getAccess($configPrefix) {
		if(!isset(self::$accesses[$configPrefix])) {
			$this->addAccess($configPrefix);
		}
		return self::$accesses[$configPrefix];
	}

	protected function getUserCacheKey($uid) {
		return 'user-'.$uid.'-lastSeenOn';
	}

	protected function getGroupCacheKey($gid) {
		return 'group-'.$gid.'-lastSeenOn';
	}

	/**
	 * @param boolean $passOnWhen
	 * @param string $method
	 */
	abstract protected function callOnLastSeenOn($id, $method, $parameters, $passOnWhen);

	/**
	 * @param string $method
	 */
	abstract protected function walkBackends($id, $method, $parameters);

	/**
	 * @brief Takes care of the request to the User backend
	 * @param $uid string, the uid connected to the request
	 * @param string $method string, the method of the user backend that shall be called
	 * @param $parameters an array of parameters to be passed
	 * @return mixed, the result of the specified method
	 */
	protected function handleRequest($id, $method, $parameters, $passOnWhen = false) {
		$result = $this->callOnLastSeenOn($id,  $method, $parameters, $passOnWhen);
		if($result === $passOnWhen) {
			$result = $this->walkBackends($id, $method, $parameters);
		}
		return $result;
	}

	/**
	 * @param string|null $key
	 */
	private function getCacheKey($key) {
		$prefix = 'LDAP-Proxy-';
		if(is_null($key)) {
			return $prefix;
		}
		return $prefix.md5($key);
	}

	/**
	 * @param string $key
	 */
	public function getFromCache($key) {
		if(!$this->isCached($key)) {
			return null;
		}
		$key = $this->getCacheKey($key);

		return unserialize(base64_decode($this->cache->get($key)));
	}

	/**
	 * @param string $key
	 */
	public function isCached($key) {
		$key = $this->getCacheKey($key);
		return $this->cache->hasKey($key);
	}

	/**
	 * @param string $key
	 */
	public function writeToCache($key, $value) {
		$key   = $this->getCacheKey($key);
		$value = base64_encode(serialize($value));
		$this->cache->set($key, $value, '2592000');
	}

	public function clearCache() {
		$this->cache->clear($this->getCacheKey(null));
	}
}
