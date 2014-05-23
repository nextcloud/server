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
use OC\Files\Filesystem;

/**
 * Class Lock
 * @package OC\Files
 */
class Lock {
	const READ = 1;
	const WRITE = 2;

	/** @var string $path Filename of the file as represented in storage */
	protected $path;

	/** @var array $stack A stack of lock data */
	protected $stack = array();

	/** @var int $retries Number of lock retries to attempt */
	public static $retries = 40;

	/** @var int $retryInterval Milliseconds between retries */
	public static $retryInterval = 50;

	/**
	 * Constructor for the lock instance
	 * @param string $path Absolute pathname for a local file on which to obtain a lock
	 */
	public function __construct($path) {
		$this->path = Filesystem::normalizePath($path);
	}

	/**
	 * @param integer $lockType A constant representing the type of lock to queue
	 */
	public function addLock($lockType) {
		\OC_Log::write('lock', sprintf('INFO: Lock type %d requested for %s', $lockType, $this->path), \OC_Log::DEBUG);
		$timeout = self::$retries;

		if(!isset($this->stack[$lockType])) {
			// does lockfile exist?
				// yes
				// Acquire exclusive lock on lockfile?
					// yes
					// Delete lockfile, release lock
					// no
					// Sleep for configurable milliseconds - start over
				// no
				// Acquire shared lock on original file?
					// yes
					// Capture handle, return for action
					// no
					// Sleep for configurable milliseconds - start over
			$handle = 1;

			$this->stack[$lockType] = array('handle' => $handle, 'count' => 0);
		}
		$this->stack[$lockType]['count']++;

	}

	/**
	 * Release locks on handles and files
	 */
	public function release($lockType) {
		return true;
	}

	/**
	 * Release all queued locks on the file
	 * @return bool
	 */
	public function releaseAll() {
		return true;
	}

}