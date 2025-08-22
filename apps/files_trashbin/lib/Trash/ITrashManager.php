<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Trash;

use OCP\IUser;

interface ITrashManager extends ITrashBackend {
	/**
	 * Add a backend for the trashbin
	 *
	 * @param string $storageType
	 * @param ITrashBackend $backend
	 * @since 15.0.0
	 */
	public function registerBackend(string $storageType, ITrashBackend $backend);

	/**
	 * List all trash items in the root of the trashbin
	 *
	 * @param IUser $user
	 * @return ITrashItem[]
	 * @since 15.0.0
	 */
	public function listTrashRoot(IUser $user): array;

	/**
	 * Temporally prevent files from being moved to the trash
	 *
	 * @since 15.0.0
	 */
	public function pauseTrash();

	/**
	 * @since 15.0.0
	 */
	public function resumeTrash();
}
