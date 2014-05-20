<?php

/**
 * Copyright (c) 2013 ownCloud, Inc.
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage\Wrapper;

use OC\Files\Filesystem;
use OCP\Files\LockNotAcquiredException;
use OCP\Files\Lock;

/**
 * Class LockingWrapper
 * A Storage Wrapper used to lock files at the system level
 * @package OC\Files\Storage\Wrapper
 *
 * Notes: Does the $locks array need to be global to all LockingWrapper instances, such as in the case of two paths
 * that point to the same physical file?  Otherwise accessing the file from a different path the second time would show
 * the file as locked, even though this process is the one locking it.
 */
class LockingWrapper extends Wrapper {

	/** @var array $locks Holds an array of lock instances indexed by path for this storage */
	protected $locks = array();

	/**
	 * Acquire a lock on a file
	 * @param string $path Path to file, relative to this storage
	 * @param integer $lockType A Lock class constant, Lock::READ/Lock::WRITE
	 * @return bool|\OCP\Files\Lock Lock instance on success, false on failure
	 */
	protected function getLock($path, $lockType){
		$path = Filesystem::normalizePath($path);
		if(!isset($this->locks[$path])) {
			$this->locks[$path] = new Lock($path);
		}
		$this->locks[$path]->addLock($lockType);
		return $this->locks[$path];
	}

	/**
	 * Release an existing lock
	 * @param string $path Path to file, relative to this storage
	 * @return bool true on success, false on failure
	 */
	protected function releaseLock($path, $lockType, $releaseAll = false){
		$path = Filesystem::normalizePath($path);
		if(isset($this->locks[$path])) {
			if($releaseAll) {
				return $this->locks[$path]->releaseAll();
			}
			else {
				return $this->locks[$path]->release($lockType);
			}
		}
		return true;
	}

	/**
	 * see http://php.net/manual/en/function.file_get_contents.php
	 * @param string $path
	 * @return string
	 * @throws \Exception
	 */
	public function file_get_contents($path) {
		try {
			if (!$this->getLock($path, Lock::READ)) {
				throw new LockNotAcquiredException($path, Lock::READ);
			}
			$result = $this->storage->file_get_contents($path);
		}
		catch(\Exception $originalException) {
			// Need to release the lock before more operations happen in upstream exception handlers
			$this->releaseLock($path, Lock::READ);
			throw $originalException;
		}
		return $result;
	}

	public function file_put_contents($path, $data) {
		try {
			if (!$this->getLock($path, Lock::WRITE)) {
				throw new LockNotAcquiredException($path, Lock::WRITE);
			}
			$result = $this->storage->file_put_contents($path, $data);
		}
		catch(\Exception $originalException) {
			// Release lock, throw original exception
			$this->releaseLock($path, Lock::WRITE);
			throw $originalException;
		}
		return $result;
	}


	public function copy($path1, $path2) {
		try {
			if (!$this->getLock($path1, Lock::READ)) {
				throw new LockNotAcquiredException($path1, Lock::READ);
			}
			if (!$this->getLock($path2, Lock::WRITE)) {
				throw new LockNotAcquiredException($path2, Lock::WRITE);
			}
			$result = $this->storage->copy($path1, $path2);
		}
		catch(\Exception $originalException) {
			// Release locks, throw original exception
			$this->releaseLock($path1, Lock::READ);
			$this->releaseLock($path2, Lock::WRITE);
			throw $originalException;
		}
		return $result;
	}

	public function rename($path1, $path2) {
		try {
			if (!$this->getLock($path1, Lock::READ)) {
				throw new LockNotAcquiredException($path1, Lock::READ);
			}
			if (!$this->getLock($path2, Lock::WRITE)) {
				throw new LockNotAcquiredException($path2, Lock::WRITE);
			}
			$result = $this->storage->rename($path1, $path2);
		}
		catch(\Exception $originalException) {
			// Release locks, throw original exception
			$this->releaseLock($path1, Lock::READ);
			$this->releaseLock($path2, Lock::WRITE);
			throw $originalException;
		}
		return $result;
	}


}