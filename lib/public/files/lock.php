<?php
/**
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

namespace OCP\Files;

use OCP\Config;
use OC\Files\Filesystem;

/**
 * Class Lock
 * @package OC\Files
 */
class Lock {
	const READ = 1;
	const WRITE = 2;

	/** @var int $retries Number of lock retries to attempt */
	public static $retries = 40;

	/** @var int $retryInterval Milliseconds between retries */
	public static $retryInterval = 50;

	/** @var string $path Filename of the file as represented in storage */
	protected $path;

	/** @var array $stack A stack of lock data */
	protected $stack = array();

	/** @var resource $handle A file handle used to maintain a lock  */
	protected $handle;

	/** @var string $lockFile Filename of the lock file */
	protected $lockFile;

	/** @var resource $lockFileHandle The file handle used to maintain a lock on the lock file */
	protected $lockFileHandle;

	/**
	 * Constructor for the lock instance
	 * @param string $path Absolute pathname for a local file on which to obtain a lock
	 */
	public function __construct($path) {
		$this->path = Filesystem::normalizePath($path);
	}

	protected function obtainReadLock($existingHandle = null) {
		\OC_Log::write('lock', sprintf('INFO: Read lock requested for %s', $this->path), \OC_Log::DEBUG);
		$timeout = Lock::$retries;

		// Re-use an existing handle or get a new one
		if(empty($existingHandle)) {
			$handle = fopen($this->path, 'r');
		}
		else {
			$handle = $existingHandle;
		}

		do {
			if($this->isLockFileLocked($this->getLockFile($this->path))) {
				\OC_Log::write('lock', sprintf('INFO: Read lock has locked lock file %s for %s', $this->getLockFile($this->path), $this->path), \OC_Log::DEBUG);
				do {
					usleep(Lock::$retryInterval);
					$timeout--;
				} while($this->isLockFileLocked($this->getLockFile($this->path)) && $timeout > 0);
				\OC_Log::write('lock', sprintf('INFO: Lock file %s has become unlocked for %s', $this->getLockFile($this->path), $this->path), \OC_Log::DEBUG);
			}
		} while ((!$lockReturn = flock($handle, LOCK_SH | LOCK_NB, $wouldBlock)) && $timeout > 0);
		if ($wouldBlock == true || $lockReturn == false || $timeout <= 0) {
			\OC_Log::write('lock', sprintf('FAIL: Failed to acquire read lock for %s', $this->path), \OC_Log::DEBUG);
			return false;
		}
		$this->handle = $handle;
		\OC_Log::write('lock', sprintf('PASS: Acquired read lock for %s', $this->path), \OC_Log::DEBUG);
		return true;
	}

	protected function obtainWriteLock($existingHandle = null) {
		\OC_Log::write('lock', sprintf('INFO: Write lock requested for %s', $this->path), \OC_Log::DEBUG);

		// Re-use an existing handle or get a new one
		if (empty($existingHandle)) {
			$handle = fopen($this->path, 'c');
		}
		else {
			$handle = $existingHandle;
		}

		// If the file doesn't exist, but we can create a lock for it
		if (!file_exists($this->path) && $this->lockLockFile($this->path)) {
			$lockReturn = flock($handle, LOCK_EX | LOCK_NB, $wouldBlock);
			if ($lockReturn == false || $wouldBlock == true) {
				\OC_Log::write('lock', sprintf('FAIL: Write lock failed, unable to exclusively lock new file %s', $this->path), \OC_Log::DEBUG);
				return false;
			}
			$this->handle = $handle;
			return true;
		}


		// Since this file does exist, wait for locks to release to get an exclusive lock
		$timeout = Lock::$retries;
		$haveBlock = false;
		while ((!$lockReturn = flock($handle, LOCK_EX | LOCK_NB, $wouldBlock)) && $timeout > 0) {
			// We don't have a lock on the original file, try to get a lock on its lock file
			if ($haveBlock || $haveBlock = $this->lockLockFile($this->lockFile)) {
				usleep(Lock::$retryInterval);
			}
			else {
				\OC_Log::write('lock', sprintf('FAIL: Write lock failed, unable to lock original %s or lock file', $this->path), \OC_Log::DEBUG);
				return false;
			}
			$timeout--;
		}
		if ($wouldBlock == true || $lockReturn == false) {
			\OC_Log::write('lock', sprintf('FAIL: Write lock failed due to timeout on %s', $this->path), \OC_Log::DEBUG);
			return false;
		}
		\OC_Log::write('lock', sprintf('PASS: Write lock succeeded on %s', $this->path), \OC_Log::DEBUG);

		return true;
	}

	/**
	 * Create a lock file and lock it
	 * Sets $this->lockFile to the specified lock file, indicating that the lock file is IN USE for this lock instance
	 * Also sets $this->lockFileHandle to a file handle of the lock file
	 * @param string $filename The name of the file to lock
	 * @param int $timeout Milliseconds to wait for a valid lock
	 * @return bool False if lock can't be acquired, true if it can.
	 */
	protected function lockLockFile ( $filename, $timeout = 0 ) {
		$lockFile = $this->getLockFile($filename);
		\OC_Log::write('lock', sprintf('INFO: Locking lock file %s for %s', $lockFile, $filename), \OC_Log::DEBUG);

		// If we already manage the lockfile, success
		if(!empty($this->lockFile)) {
			\OC_Log::write('lock', sprintf('PASS: Lock file %s was locked by this request for %s', $lockFile, $filename), \OC_Log::DEBUG);
			return true;
		}

		// Check if the lockfile exists, and if not, try to create it
		\OC_Log::write('lock', sprintf('INFO: Does lock file %s already exist?  %s', $lockFile, file_exists($lockFile) ? 'yes' : 'no'), \OC_Log::DEBUG);
		$handle = fopen($lockFile, 'c');
		if(!$handle) {
			\OC_Log::write('lock', sprintf('FAIL: Could not create lock file %s', $lockFile), \OC_Log::DEBUG);
			return false;
		}

		// Attempt to acquire lock on lock file
		$wouldBlock = false;
		$timeout = self::$retries;
		// Wait for lock over timeout
		while((!$lockReturn = flock($handle, LOCK_EX | LOCK_NB, $wouldBlock)) && $timeout > 0) {
			\OC_Log::write('lock', sprintf('FAIL: Could not acquire lock on lock file %s, %s timeout increments remain.', $lockFile, $timeout), \OC_Log::DEBUG);
			usleep(self::$retryInterval);
			$timeout--;
		}
		if ($wouldBlock == true || $lockReturn == false) {
			\OC_Log::write('lock', sprintf('FAIL: Could not acquire lock on lock file %s', $lockFile), \OC_Log::DEBUG);
			return false;
		}
		fwrite($handle, $filename);
		\OC_Log::write('lock', sprintf('PASS: Wrote filename to lock lock file %s', $lockFile), \OC_Log::DEBUG);

		$this->lockFile = $lockFile;
		$this->lockFileHandle = $handle;

		return true;
	}

	/**
	 * Add a lock of a specific type to the stack
	 * @param integer $lockType A constant representing the type of lock to queue
	 * @param null|resource $existingHandle An existing file handle from an fopen()
	 * @throws LockNotAcquiredException
	 */
	public function addLock($lockType, $existingHandle = null) {
		if(!isset($this->stack[$lockType])) {
			switch($lockType) {
				case Lock::READ:
					$result = $this->obtainReadLock($existingHandle);
					break;
				case Lock::WRITE:
					$result = $this->obtainWriteLock($existingHandle);
					break;
				default:
					$result = false;
					break;
			}
			if($result) {
				$this->stack[$lockType] = 0;
			}
			else {
				throw new LockNotAcquiredException($this->path, $lockType);
			}
		}

		\OC_Log::write('lock', sprintf('INFO: Incrementing lock type %d count for %s', $lockType, $this->path), \OC_Log::DEBUG);
		$this->stack[$lockType]++;

	}

	/**
	 * Release locks on handles and files
	 */
	public function release($lockType) {
		if(isset($this->stack[$lockType])) {
			$this->stack[$lockType]--;
			if($this->stack[$lockType] <= 0) {
				unset($this->stack[$lockType]);
			}
		}

		if(count($this->stack) == 0) {
			// No more locks needed on this file, release the handle and/or lockfile
			$this->releaseAll();
		}

		return true;
	}


	/**
	 * Get the lock file associated to a file
	 * @param string $filename The filename of the file to create a lock file for
	 * @return string The filename of the lock file
	 */
	public static function getLockFile($filename) {
		static $locksDir = false;
		if(!$locksDir) {
			$dataDir = Config::getSystemValue('datadirectory');
			$locksDir = $dataDir . '/.locks';
			if(!file_exists($locksDir)) {
				mkdir($locksDir);
			}
		}
		$filename = Filesystem::normalizePath($filename);
		return $locksDir . '/' . sha1($filename) . '.lock';
	}

	/**
	 * Determine if a file has an associated and flocked lock file
	 * @param string $lockFile The filename of the lock file to check
	 * @return bool True if the lock file is flocked
	 */
	protected function isLockFileLocked($lockFile) {
		if(file_exists($lockFile)) {
			if($handle = fopen($lockFile, 'c')) {
				if($lock = flock($handle, LOCK_EX | LOCK_NB)) {
					// Got lock, not blocking, release and unlink
					unlink($lockFile);
					fclose($handle);
					flock($lock, LOCK_UN);
					return false;
				}
				else {
					return true;
				}
			}
			else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Release all queued locks on the file
	 * @return bool
	 */
	public function releaseAll() {
		$this->stack = array();
		\OC_Log::write('lock', sprintf('INFO: Releasing locks on %s', $this->path), \OC_Log::DEBUG);
		if (!empty($this->handle) && is_resource($this->handle)) {
			flock($this->handle, LOCK_UN);
			\OC_Log::write('lock', sprintf('INFO: Released lock handle %s on %s', $this->handle, $this->path), \OC_Log::DEBUG);
			$this->handle = null;
		}
		if (!empty($this->lockFile) && file_exists($this->lockFile)) {
			unlink($this->lockFile);
			\OC_Log::write('lock', sprintf('INFO: Released lock file %s on %s', $this->lockFile, $this->path), \OC_Log::DEBUG);
			$this->lockFile = null;
		}
		\OC_Log::write('lock', sprintf('FREE: Released locks on %s', $this->path), \OC_Log::DEBUG);
		return true;
	}

	public function __destruct() {
		// Only releaseAll if we have locks to release
		if(!empty($this->handle) || !empty($this->lockFile)) {
			\OC_Log::write('lock', sprintf('INFO: Destroying locks on %s', $this->path), \OC_Log::DEBUG);
			$this->releaseAll();
		}
	}

}