<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Mount;

use OC\Files\ObjectStore\HomeObjectStoreStorage;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OCP\Files\Config\IHomeMountProvider;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

/**
 * Mount provider for object store home storages
 */
class ObjectHomeMountProvider implements IHomeMountProvider {
	public function __construct(
		private PrimaryObjectStoreConfig $objectStoreConfig,
	) {
	}

	/**
	 * Get the home mount for a user
	 *
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 * @return ?IMountPoint
	 */
	public function getHomeMountForUser(IUser $user, IStorageFactory $loader): ?IMountPoint {
		$objectStoreConfig = $this->objectStoreConfig->getObjectStoreConfigForUser($user);
		if ($objectStoreConfig === null) {
			return null;
		}
		$arguments = array_merge($objectStoreConfig['arguments'], [
			'objectstore' => $this->objectStoreConfig->buildObjectStore($objectStoreConfig),
			'user' => $user,
		]);

		return new HomeMountPoint($user, HomeObjectStoreStorage::class, '/' . $user->getUID(), $arguments, $loader, null, null, self::class);
	}
}
