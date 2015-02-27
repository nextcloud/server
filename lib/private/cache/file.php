<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Cache;

class File {
	protected $storage;

	/**
	 * Returns the cache storage for the logged in user
	 * @return \OC\Files\View cache storage
	 */
	protected function getStorage() {
		if (isset($this->storage)) {
			return $this->storage;
		}
		if(\OC_User::isLoggedIn()) {
			\OC\Files\Filesystem::initMountPoints(\OC_User::getUser());
			$this->storage = new \OC\Files\View('/' . \OC_User::getUser() . '/cache');
			return $this->storage;
		}else{
			\OC_Log::write('core', 'Can\'t get cache storage, user not logged in', \OC_Log::ERROR);
			throw new \OC\ForbiddenException('Can\t get cache storage, user not logged in');
		}
	}

	/**
	 * @param string $key
	 */
	public function get($key) {
		$result = null;
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;
		if ($this->hasKey($key)) {
			$storage = $this->getStorage();
			$result = $storage->file_get_contents($key);
		}
		\OC_FileProxy::$enabled = $proxyStatus;
		return $result;
	}

	/**
	 * Returns the size of the stored/cached data
	 *
	 * @param string $key
	 * @return int
	 */
	public function size($key) {
		$result = 0;
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;
		if ($this->hasKey($key)) {
			$storage = $this->getStorage();
			$result = $storage->filesize($key);
		}
		\OC_FileProxy::$enabled = $proxyStatus;
		return $result;
	}

	/**
	 * @param string $key
	 */
	public function set($key, $value, $ttl=0) {
		$storage = $this->getStorage();
		$result = false;
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;
		if ($storage and $storage->file_put_contents($key, $value)) {
			if ($ttl === 0) {
				$ttl = 86400; // 60*60*24
			}
			$result = $storage->touch($key, time() + $ttl);
		}
		\OC_FileProxy::$enabled = $proxyStatus;
		return $result;
	}

	public function hasKey($key) {
		$storage = $this->getStorage();
		if ($storage && $storage->is_file($key) && $storage->isReadable($key)) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $key
	 */
	public function remove($key) {
		$storage = $this->getStorage();
		if(!$storage) {
			return false;
		}
		return $storage->unlink($key);
	}

	public function clear($prefix='') {
		$storage = $this->getStorage();
		if($storage and $storage->is_dir('/')) {
			$dh=$storage->opendir('/');
			if(is_resource($dh)) {
				while (($file = readdir($dh)) !== false) {
					if($file!='.' and $file!='..' and ($prefix==='' || strpos($file, $prefix) === 0)) {
						$storage->unlink('/'.$file);
					}
				}
			}
		}
		return true;
	}

	public function gc() {
		$storage = $this->getStorage();
		if($storage and $storage->is_dir('/')) {
			$now = time();
			$dh=$storage->opendir('/');
			if(!is_resource($dh)) {
				return null;
			}
			while (($file = readdir($dh)) !== false) {
				if($file!='.' and $file!='..') {
					$mtime = $storage->filemtime('/'.$file);
					if ($mtime < $now) {
						$storage->unlink('/'.$file);
					}
				}
			}
		}
	}

	public static function loginListener() {
		$c = new self();
		$c->gc();
	}
}
