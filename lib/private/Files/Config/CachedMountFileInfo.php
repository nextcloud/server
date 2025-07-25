<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Config;

use OCP\Files\Config\ICachedMountFileInfo;
use OCP\IUser;

class CachedMountFileInfo extends CachedMountInfo implements ICachedMountFileInfo {
	private string $internalPath;

	public function __construct(
		IUser $user,
		int $storageId,
		int $rootId,
		string $mountPoint,
		?int $mountId,
		string $mountProvider,
		string $rootInternalPath,
		string $internalPath,
	) {
		parent::__construct($user, $storageId, $rootId, $mountPoint, $mountProvider, $mountId, $rootInternalPath);
		$this->internalPath = $internalPath;
	}

	public function getInternalPath(): string {
		if ($this->getRootInternalPath()) {
			return substr($this->internalPath, strlen($this->getRootInternalPath()) + 1);
		} else {
			return $this->internalPath;
		}
	}

	public function getPath(): string {
		return $this->getMountPoint() . $this->getInternalPath();
	}
}
