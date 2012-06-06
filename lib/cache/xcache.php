<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Cache_XCache {
	/**
	 * entries in XCache gets namespaced to prevent collisions between owncloud instances and users
	 */
	protected function getNameSpace() {
		return OC_Util::getInstanceId().'/'.OC_User::getUser().'/';
	}

	public function get($key) {
		return xcache_get($this->getNamespace().$key);
	}

	public function set($key, $value, $ttl=0) {
		if($ttl>0){
			return xcache_set($this->getNamespace().$key,$value,$ttl);
		}else{
			return xcache_set($this->getNamespace().$key,$value);
		}
	}

	public function hasKey($key) {
		return xcache_isset($this->getNamespace().$key);
	}

	public function remove($key) {
		return xcache_unset($this->getNamespace().$key);
	}

	public function clear(){
		return xcache_unset_by_prefix($this->getNamespace());
	}
}
