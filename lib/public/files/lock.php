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

/**
 * Class Lock
 * @package OC\Files
 */
interface Lock {
	const READ = 1;
	const WRITE = 2;

	/**
	 * Constructor for the lock instance
	 * @param string $path Absolute pathname for a local file on which to obtain a lock
	 */
	public function __construct($path);


	/**
	 * Add a lock of a specific type to the stack
	 * @param integer $lockType A constant representing the type of lock to queue
	 * @param null|resource $existingHandle An existing file handle from an fopen()
	 * @throws LockNotAcquiredException
	 */
	public function addLock($lockType, $existingHandle = null);

	/**
	 * Release locks on handles and files
	 */
	public function release($lockType);


	/**
	 * Get the lock file associated to a file
	 * @param string $filename The filename of the file to create a lock file for
	 * @return string The filename of the lock file
	 */
	public static function getLockFile($filename);

	/**
	 * Release all queued locks on the file
	 * @return bool
	 */
	public function releaseAll();

}