<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Trash;

use OCP\Files\FileInfo;
use OCP\IUser;

/**
 * @since 15.0.0
 */
interface ITrashItem extends FileInfo {
	/**
	 * Get the trash backend for this item
	 *
	 * @return ITrashBackend
	 * @since 15.0.0
	 */
	public function getTrashBackend(): ITrashBackend;

	/**
	 * Get the original location for the trash item
	 *
	 * @return string
	 * @since 15.0.0
	 */
	public function getOriginalLocation(): string;

	/**
	 * Get the timestamp that the file was moved to trash
	 *
	 * @return int
	 * @since 15.0.0
	 */
	public function getDeletedTime(): int;

	/**
	 * Get the path of the item relative to the users trashbin
	 *
	 * @return string
	 * @since 15.0.0
	 */
	public function getTrashPath(): string;

	/**
	 * Whether the item is a deleted item in the root of the trash, or a file in a subfolder
	 *
	 * @return bool
	 * @since 15.0.0
	 */
	public function isRootItem(): bool;

	/**
	 * Get the user for which this trash item applies
	 *
	 * @return IUser
	 * @since 15.0.0
	 */
	public function getUser(): IUser;

	/**
	 * @since 30.0.0
	 */
	public function getDeletedBy(): ?IUser;

	public function getTitle(): string;
}
