<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Cache_APC {
	/**
	 * entries in APC gets namespaced to prevent collisions between owncloud instances and users
	 */
	protected function getNameSpace() {
		return OC_Util::getInstanceId().'/'.OC_User::getUser().'/';
	}

	public function get($key) {
		$result = apc_fetch($this->getNamespace().$key, $success);
		if (!$success) {
			return null;
		}
		return $result;
	}

	public function set($key, $value, $ttl=0) {
		return apc_store($this->getNamespace().$key, $value, $ttl);
	}

	public function hasKey($key) {
		return apc_exists($this->getNamespace().$key);
	}

	public function remove($key) {
		return apc_delete($this->getNamespace().$key);
	}

	public function clear(){
		$ns = $this->getNamespace();
		$cache = apc_cache_info('user');
		foreach($cache['cache_list'] as $entry) {
			if (strpos($entry['info'], $ns) === 0) {
				apc_delete($entry['info']);
			}
		}
	}
}
