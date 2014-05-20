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
class Lock {
	const READ = 1;
	const WRITE = 2;

	/** @var string $path Filename of the file as represented in storage */
	protected $path;

	public function __construct($path) {
		$this->path = $path;
	}

	public function addLock($lockType) {
		// This class is a stub/base for classes that implement locks
		// We don't actually care what kind of lock we're queuing here
	}

	/**
	 * Release locks on handles and files
	 */
	public function release($lockType) {
		return true;
	}

}