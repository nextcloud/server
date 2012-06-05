<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


class OC_Cache_File{
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
		if ($this->hasKey($key)) {
			$storage = $this->getStorage();
			return $storage->file_get_contents($key);
		}
		return null;
	}

	public function set($key, $value, $ttl=0) {
		$storage = $this->getStorage();
		if ($storage and $storage->file_put_contents($key, $value)) {
			if ($ttl === 0) {
				$ttl = 86400; // 60*60*24
			}
			return $storage->touch($key, time() + $ttl);
		}
		return false;
	}

	public function hasKey($key) {
		$storage = $this->getStorage();
		if ($storage && $storage->is_file($key)) {
			$mtime = $storage->filemtime($key);
			if ($mtime < time()) {
				$storage->unlink($key);
				return false;
			}
			return true;
		}
		return false;
	}

	public function remove($key) {
		$storage = $this->getStorage();
		if(!$storage){
			return false;
		}
		return $storage->unlink($key);
	}

	public function clear(){
		$storage = $this->getStorage();
		if($storage and $storage->is_dir('/')){
			$dh=$storage->opendir('/');
			while($file=readdir($dh)){
				if($file!='.' and $file!='..'){
					$storage->unlink('/'.$file);
				}
			}
		}
	}
}
