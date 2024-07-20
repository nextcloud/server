<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Trash;

use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IUser;

/**
 * @since 15.0.0
 */
interface ITrashBackend {
	/**
	 * List all trash items in the root of the trashbin
	 *
	 * @param IUser $user
	 * @return ITrashItem[]
	 * @since 15.0.0
	 */
	public function listTrashRoot(IUser $user): array;

	/**
	 * List all trash items in a subfolder in the trashbin
	 *
	 * @param ITrashItem $folder
	 * @return ITrashItem[]
	 * @since 15.0.0
	 */
	public function listTrashFolder(ITrashItem $folder): array;

	/**
	 * Restore a trashbin item
	 *
	 * @param ITrashItem $item
	 * @since 15.0.0
	 */
	public function restoreItem(ITrashItem $item);

	/**
	 * Permanently remove an item from trash
	 *
	 * @param ITrashItem $item
	 * @since 15.0.0
	 */
	public function removeItem(ITrashItem $item);

	/**
	 * Move a file or folder to trash
	 *
	 * @param IStorage $storage
	 * @param string $internalPath
	 * @return boolean whether or not the file was moved to trash, if false then the file should be deleted normally
	 * @since 15.0.0
	 */
	public function moveToTrash(IStorage $storage, string $internalPath): bool;

	/**
	 * @param IUser $user
	 * @param int $fileId
	 * @return Node|null
	 */
	public function getTrashNodeById(IUser $user, int $fileId);
}
