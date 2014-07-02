<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\External;

class Scanner extends \OC\Files\Cache\Scanner {
	/**
	 * @var \OCA\Files_Sharing\External\Storage
	 */
	protected $storage;

	public function scan($path, $recursive = self::SCAN_RECURSIVE, $reuse = -1) {
		$this->scanAll();
	}

	public function scanAll() {
		$data = $this->storage->getShareInfo();
		if ($data['status'] === 'success') {
			$this->addResult($data['data'], '');
		} else {
			throw new \Exception('Error while scanning remote share');
		}
	}

	private function addResult($data, $path) {
		$this->cache->put($path, $data);
		if (isset($data['children'])) {
			foreach ($data['children'] as $child) {
				$this->addResult($child, ltrim($path . '/' . $child['name'], '/'));
			}
		}
	}
}
