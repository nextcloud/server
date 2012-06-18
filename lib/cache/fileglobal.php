<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


class OC_Cache_FileGlobal{
	protected function getCacheDir() {
		$cache_dir = get_temp_dir().'/owncloud-'.OC_Util::getInstanceId().'/';
		if (!is_dir($cache_dir)) {
			mkdir($cache_dir);
		}
		return $cache_dir;
	}

	protected function fixKey($key) {
		return str_replace('/', '_', $key);
	}

	public function get($key) {
		$key = $this->fixKey($key);
		if ($this->hasKey($key)) {
			$cache_dir = $this->getCacheDir();
			return file_get_contents($cache_dir.$key);
		}
		return null;
	}

	public function set($key, $value, $ttl=0) {
		$key = $this->fixKey($key);
		$cache_dir = $this->getCacheDir();
		if ($cache_dir and file_put_contents($cache_dir.$key, $value)) {
			if ($ttl === 0) {
				$ttl = 86400; // 60*60*24
			}
			return touch($cache_dir.$key, time() + $ttl);
		}
		return false;
	}

	public function hasKey($key) {
		$key = $this->fixKey($key);
		$cache_dir = $this->getCacheDir();
		if ($cache_dir && is_file($cache_dir.$key)) {
			$mtime = filemtime($cache_dir.$key);
			if ($mtime < time()) {
				unlink($cache_dir.$key);
				return false;
			}
			return true;
		}
		return false;
	}

	public function remove($key) {
		$cache_dir = $this->getCacheDir();
		if(!$cache_dir){
			return false;
		}
		$key = $this->fixKey($key);
		return unlink($cache_dir.$key);
	}

	public function clear(){
		$cache_dir = $this->getCacheDir();
		if($cache_dir and is_dir($cache_dir)){
			$dh=opendir($cache_dir);
			while($file=readdir($dh)){
				if($file!='.' and $file!='..'){
					unlink($cache_dir.$file);
				}
			}
		}
	}
}
