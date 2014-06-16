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
		$remote = $this->storage->getRemote();
		$token = $this->storage->getToken();
		$password = $this->storage->getPassword();
		$url = $remote . '/index.php/apps/files_sharing/shareinfo?t=' . $token;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			http_build_query(array('password' => $password)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($ch);
		curl_close($ch);

		$data = json_decode($result, true);
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
