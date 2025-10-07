<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;
use OCP\Files\Node as INode;
use Override;

/**
 * Class LazyRoot
 *
 * This is a lazy wrapper around the root. So only
 * once it is needed this will get initialized.
 *
 * @package OC\Files\Node
 */
class LazyRoot extends LazyFolder implements IRootFolder {
	public function __construct(\Closure $folderClosure, array $data = []) {
		parent::__construct($this, $folderClosure, $data);
	}

	protected function getRootFolder(): IRootFolder {
		$folder = $this->getRealFolder();
		if (!$folder instanceof IRootFolder) {
			throw new \Exception('Lazy root folder closure didn\'t return a root folder');
		}
		return $folder;
	}

	#[Override]
	public function listen($scope, $method, callable $callback): void {
		$this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function removeListener($scope = null, $method = null, ?callable $callback = null): void {
		$this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getUserFolder(string $userId): \OCP\Files\Folder {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getByIdInPath(int $id, string $path): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getFirstNodeByIdInPath(int $id, string $path): ?Node {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getNodeFromCacheEntryAndMount(ICacheEntry $cacheEntry, IMountPoint $mountPoint): INode {
		return $this->getRootFolder()->getNodeFromCacheEntryAndMount($cacheEntry, $mountPoint);
	}

	#[Override]
	public function getAppDataDirectoryName(): string {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getMountsIn(string $mountPoint): array {
		return $this->__call(__FUNCTION__, func_get_args());
	}

	#[Override]
	public function getMount(string $mountPoint): IMountPoint {
		return $this->__call(__FUNCTION__, func_get_args());
	}
}
