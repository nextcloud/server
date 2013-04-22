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

abstract class Proxy {
	static private $connectors = array();

	public function __construct() {
		$this->cache = \OC_Cache::getGlobalCache();
	}

	private function addConnector($configPrefix) {
		self::$connectors[$configPrefix] = new \OCA\user_ldap\lib\Connection($configPrefix);
	}

	protected function getConnector($configPrefix) {
		if(!isset(self::$connectors[$configPrefix])) {
			$this->addConnector($configPrefix);
		}
		return self::$connectors[$configPrefix];
	}

	protected function getConnectors() {
		return self::$connectors;
	}

	protected function getUserCacheKey($uid) {
		return 'user-'.$uid.'-lastSeenOn';
	}

	protected function getGroupCacheKey($gid) {
		return 'group-'.$gid.'-lastSeenOn';
	}

	abstract protected function callOnLastSeenOn($id, $method, $parameters);
	abstract protected function walkBackends($id, $method, $parameters);

	/**
	 * @brief Takes care of the request to the User backend
	 * @param $uid string, the uid connected to the request
	 * @param $method string, the method of the user backend that shall be called
	 * @param $parameters an array of parameters to be passed
	 * @return mixed, the result of the specified method
	 */
	protected function handleRequest($id, $method, $parameters) {
		if(!$result = $this->callOnLastSeenOn($id,  $method, $parameters)) {
			$result = $this->walkBackends($id, $method, $parameters);
		}
		return $result;
	}

	private function getCacheKey($key) {
		$prefix = 'LDAP-Proxy-';
		if(is_null($key)) {
			return $prefix;
		}
		return $prefix.md5($key);
	}

	public function getFromCache($key) {
		if(!$this->isCached($key)) {
			return null;
		}
		$key = $this->getCacheKey($key);

		return unserialize(base64_decode($this->cache->get($key)));
	}

	public function isCached($key) {
		$key = $this->getCacheKey($key);
		return $this->cache->hasKey($key);
	}

	public function writeToCache($key, $value) {
		$key   = $this->getCacheKey($key);
		$value = base64_encode(serialize($value));
		$this->cache->set($key, $value, '2592000');
	}

	public function clearCache() {
		$this->cache->clear($this->getCacheKey(null));
	}
}