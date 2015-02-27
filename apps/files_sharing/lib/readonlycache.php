<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing;

use OC\Files\Cache\Cache;

class ReadOnlyCache extends Cache {
	public function get($path) {
		$data = parent::get($path);
		$data['permissions'] &= (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE);
		return $data;
	}

	public function getFolderContents($path) {
		$content = parent::getFolderContents($path);
		foreach ($content as &$data) {
			$data['permissions'] &= (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE);
		}
		return $content;
	}
}
