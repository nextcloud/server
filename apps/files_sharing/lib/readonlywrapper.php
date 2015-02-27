<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing;

use OC\Files\Storage\Wrapper\Wrapper;

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
		return new ReadOnlyCache($storage);
	}
}
