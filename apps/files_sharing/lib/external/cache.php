<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\External;

class Cache extends \OC\Files\Cache\Cache {
	private $remote;
	private $remoteUser;
	private $storage;

	/**
	 * @param \OCA\Files_Sharing\External\Storage $storage
	 * @param string $remote
	 * @param string $remoteUser
	 */
	public function __construct($storage, $remote, $remoteUser) {
		$this->storage = $storage;
		list(, $remote) = explode('://', $remote, 2);
		$this->remote = $remote;
		$this->remoteUser = $remoteUser;
		parent::__construct($storage);
	}

	public function get($file) {
		$result = parent::get($file);
		if (!$result) {
			return false;
		}
		$result['displayname_owner'] = $this->remoteUser . '@' . $this->remote;
		if (!$file || $file === '') {
			$result['is_share_mount_point'] = true;
			$mountPoint = rtrim($this->storage->getMountPoint());
			$result['name'] = basename($mountPoint);
		}
		return $result;
	}

	public function getFolderContentsById($id) {
		$results = parent::getFolderContentsById($id);
		foreach ($results as &$file) {
			$file['displayname_owner'] = $this->remoteUser . '@' . $this->remote;
		}
		return $results;
	}
}
