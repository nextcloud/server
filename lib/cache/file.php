<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


class OC_Cache_File {
	protected function getStorage() {
		if(OC_User::isLoggedIn()){
			$subdir = 'cache';
			$view = new OC_FilesystemView('/'.OC_User::getUser());
			if(!$view->file_exists($subdir)) {
				$view->mkdir($subdir);
			}
			return new OC_FilesystemView('/'.OC_User::getUser().'/'.$subdir);
		}else{
			OC_Log::write('core','Can\'t get cache storage, user not logged in', OC_Log::ERROR);
			return false;
		}
	}

	public function get($key) {
		$storage = $this->getStorage();
		if ($storage->is_file($key)) {
			$mtime = $storage->filemtime($key);
			if ($mtime < time()) {
				$storage->unlink($key);
				return false;
			}
			return $storage->file_get_contents($key);
		}
		return false;
	}

	public function set($key, $value, $ttl) {
		$storage = $this->getStorage();
		if ($storage->file_put_contents($key, $value)) {
			return $storage->touch($key, time() + $ttl);
		}
		return false;
	}

	public function remove($key) {
		$storage = $this->getStorage();
		return $storage->unlink($key);
	}
}
