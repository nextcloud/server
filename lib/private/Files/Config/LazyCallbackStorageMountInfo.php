<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Config;

use OCP\IUser;
use Override;

class LazyCallbackStorageMountInfo extends CachedMountInfo {
	private bool $loaded = false;

	/**
	 * @param IUser $user
	 * @param string $mountPoint
	 * @param Closure():CachedMountInfo $callback
	 * @throws \Exception
	 */
	public function __construct(
		IUser $user,
		string $mountPoint,
		private $callback,
	) {
		parent::__construct($user, 0, 0, $mountPoint, '');
		$this->key = '';
	}

	private function load() {
		if (!$this->loaded) {
			return;
		}
		$info = ($this->callback)();
		$this->storageId = $info->storageId;
		$this->rootId = $info->rootId;
		$this->mountProvider = $info->mountProvider;
		$this->mountId = $info->mountId;
	}

	#[Override]
	public function getStorageId(): int {
		$this->load();
		return parent::getStorageId();
	}

	#[Override]
	public function getRootId(): int {
		$this->load();
		return parent::getRootId();
	}

	#[Override]
	public function getMountId(): ?int {
		$this->load();
		return parent::getMountId();
	}

	#[Override]
	public function getRootInternalPath(): string {
		$this->load();
		return parent::getRootInternalPath();
	}

	#[Override]
	public function getMountProvider(): string {
		$this->load();
		return parent::getMountProvider();
	}

	#[Override]
	public function getKey(): string {
		if (!$this->key) {
			$this->key = $this->getRootId() . '::' . $this->getMountPoint();
		}
		return $this->key;
	}
}
