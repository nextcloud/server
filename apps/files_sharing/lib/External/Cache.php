<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use OCP\Federation\ICloudId;

class Cache extends \OC\Files\Cache\Cache {
	private $remote;
	private $remoteUser;

	/**
	 * @param Storage $storage
	 * @param ICloudId $cloudId
	 */
	public function __construct(
		private $storage,
		private ICloudId $cloudId,
	) {
		[, $remote] = explode('://', $this->cloudId->getRemote(), 2);
		$this->remote = $remote;
		$this->remoteUser = $this->cloudId->getUser();
		parent::__construct($this->storage);
	}

	public function get($file) {
		$result = parent::get($file);
		if (!$result) {
			return false;
		}
		$result['displayname_owner'] = $this->cloudId->getDisplayId();
		if (!$file || $file === '') {
			$result['is_share_mount_point'] = true;
			$mountPoint = rtrim($this->storage->getMountPoint());
			$result['name'] = basename($mountPoint);
		}
		return $result;
	}

	public function getFolderContentsById($fileId) {
		$results = parent::getFolderContentsById($fileId);
		foreach ($results as &$file) {
			$file['displayname_owner'] = $this->cloudId->getDisplayId();
		}
		return $results;
	}
}
