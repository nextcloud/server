<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vlastimil Pecinka <pecinka@email.cz>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
