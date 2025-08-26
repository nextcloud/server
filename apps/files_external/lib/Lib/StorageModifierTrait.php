<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

use OCP\Files\Storage\IStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\IUser;

/**
 * Trait for objects that can modify StorageConfigs and wrap Storages
 *
 * When a storage implementation is being prepared for use, the StorageConfig
 * is passed through manipulateStorageConfig() to update any parameters as
 * necessary. After the storage implementation has been constructed, it is
 * passed through wrapStorage(), potentially replacing the implementation with
 * a wrapped storage that changes its behaviour.
 *
 * Certain configuration options need to be set before the implementation is
 * constructed, while others are retrieved directly from the storage
 * implementation and so need a wrapper to be modified.
 */
trait StorageModifierTrait {

	/**
	 * Modify a StorageConfig parameters
	 *
	 * @param StorageConfig &$storage
	 * @param ?IUser $user User the storage is being used as
	 * @return void
	 * @throws InsufficientDataForMeaningfulAnswerException
	 * @throws StorageNotAvailableException
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
	}

	/**
	 * Wrap a storage if necessary
	 *
	 * @throws InsufficientDataForMeaningfulAnswerException
	 * @throws StorageNotAvailableException
	 */
	public function wrapStorage(IStorage $storage): IStorage {
		return $storage;
	}
}
