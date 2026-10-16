<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Config;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\ICachedMountFileInfo;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Node;
use OCP\IUser;

class CachedMountFileInfo implements ICachedMountFileInfo {
	public function __construct(
		private readonly ICachedMountInfo $mountInfo,
		private readonly string $internalPath,
	) {
	}

	#[\Override]
	public function getUser(): IUser {
		return $this->mountInfo->getUser();
	}

	#[\Override]
	public function getStorageId(): int {
		return $this->mountInfo->getStorageId();
	}

	#[\Override]
	public function getRootId(): int {
		return $this->mountInfo->getRootId();
	}

	#[\Override]
	public function getMountPointNode(): ?Node {
		return $this->mountInfo->getMountPointNode();
	}

	#[\Override]
	public function getMountPoint(): string {
		return $this->mountInfo->getMountPoint();
	}

	#[\Override]
	public function getMountId(): ?int {
		return $this->mountInfo->getMountId();
	}

	#[\Override]
	public function getRootInternalPath(): string {
		return $this->mountInfo->getRootInternalPath();
	}

	#[\Override]
	public function getMountProvider(): string {
		return $this->mountInfo->getMountProvider();
	}

	#[\Override]
	public function getKey(): string {
		return $this->mountInfo->getKey();
	}

	#[\Override]
	public function getRootCacheEntry(): ICacheEntry {
		return $this->mountInfo->getRootCacheEntry();
	}


	#[\Override]
	public function getInternalPath(): string {
		if ($this->getRootInternalPath()) {
			return substr($this->internalPath, strlen($this->getRootInternalPath()) + 1);
		} else {
			return $this->internalPath;
		}
	}

	#[\Override]
	public function getPath(): string {
		return $this->getMountPoint() . $this->getInternalPath();
	}
}
