<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing;

use OC\Files\Cache\Wrapper\CachePermissionsMask;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Constants;

class ReadOnlyWrapper extends Wrapper {
	public function isUpdatable($path) {
		return false;
	}

	public function isCreatable($path) {
		return false;
	}

	public function isDeletable($path) {
		return false;
	}

	public function getPermissions($path) {
		return $this->storage->getPermissions($path) & (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE);
	}

	public function rename($path1, $path2) {
		return false;
	}

	public function touch($path, $mtime = null) {
		return false;
	}

	public function mkdir($path) {
		return false;
	}

	public function rmdir($path) {
		return false;
	}

	public function unlink($path) {
		return false;
	}

	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		$sourceCache = $this->storage->getCache($path, $storage);
		return new CachePermissionsMask($sourceCache, Constants::PERMISSION_READ | Constants::PERMISSION_SHARE);
	}
}
