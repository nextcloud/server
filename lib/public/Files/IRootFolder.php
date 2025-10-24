<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files;

use OC\Hooks\Emitter;
use OC\User\NoUserException;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node as INode;

/**
 * Interface IRootFolder
 *
 * Hooks available in scope \OC\Files. These are deprecated.
 * - preWrite(\OCP\Files\Node $node)
 * - postWrite(\OCP\Files\Node $node)
 * - preCreate(\OCP\Files\Node $node)
 * - postCreate(\OCP\Files\Node $node)
 * - preDelete(\OCP\Files\Node $node)
 * - postDelete(\OCP\Files\Node $node)
 * - preTouch(\OC\FilesP\Node $node, int $mtime)
 * - postTouch(\OCP\Files\Node $node)
 * - preCopy(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - postCopy(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - preRename(\OCP\Files\Node $source, \OCP\Files\Node $target)
 * - postRename(\OCP\Files\Node $source, \OCP\Files\Node $target)
 *
 * @since 8.0.0
 */
interface IRootFolder extends Folder, Emitter {
	/**
	 * Returns a view to user's files folder.
	 *
	 * @param string $userId user ID
	 * @throws NoUserException
	 * @throws NotPermittedException
	 *
	 * @since 8.2.0
	 */
	public function getUserFolder(string $userId): Folder;

	/**
	 * Get a file or folder by fileId, inside a parent path.
	 *
	 * @param int $id
	 * @param string $path
	 * @return Node[]
	 *
	 * @since 24.0.0
	 */
	public function getByIdInPath(int $id, string $path): array;

	/**
	 * Get a file or folder inside the folder by its internal id.
	 *
	 * Unlike getByIdInPath, this method only returns a single node even if the user has
	 * access to the file with the requested id multiple times.
	 *
	 * This method provides no guarantee about which of the nodes in returned and the
	 * returned node might, for example, have less permissions than other nodes for the same file
	 *
	 * Apps that require accurate information about the users access to the file should use getByIdInPath
	 * instead of pick the correct node out of the result.
	 *
	 * @since 29.0.0
	 */
	public function getFirstNodeByIdInPath(int $id, string $path): ?Node;

	/**
	 * @return IMountPoint[]
	 *
	 * @since 28.0.0
	 */
	public function getMountsIn(string $mountPoint): array;

	/**
	 * Create a `Node` for a file or folder from the cache entry and mountpoint.
	 *
	 * @param ICacheEntry $cacheEntry
	 * @param IMountPoint $mountPoint
	 * @return Node
	 * @since 28.0.0
	 */
	public function getNodeFromCacheEntryAndMount(ICacheEntry $cacheEntry, IMountPoint $mountPoint): INode;

	/**
	 * @since 28.0.0
	 */
	public function getMount(string $mountPoint): IMountPoint;

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getAppDataDirectoryName(): string;
}
