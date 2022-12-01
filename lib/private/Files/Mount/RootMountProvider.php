<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\Mount;

use OC;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\Storage\LocalRootStorage;
use OCP\Files\Config\IRootMountProvider;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;

class RootMountProvider implements IRootMountProvider {
	private PrimaryObjectStoreConfig $objectStoreConfig;
	private IConfig $config;

	public function __construct(PrimaryObjectStoreConfig $objectStoreConfig, IConfig $config) {
		$this->objectStoreConfig = $objectStoreConfig;
		$this->config = $config;
	}

	public function getRootMounts(IStorageFactory $loader): array {
		$objectStore = $this->objectStoreConfig->getObjectStoreForRoot();

		if ($objectStore) {
			return [$this->getObjectStoreRootMount($loader, $objectStore)];
		} else {
			return [$this->getLocalRootMount($loader)];
		}
	}

	private function getLocalRootMount(IStorageFactory $loader): MountPoint {
		$configDataDirectory = $this->config->getSystemValue("datadirectory", OC::$SERVERROOT . "/data");
		return new MountPoint(LocalRootStorage::class, '/', ['datadir' => $configDataDirectory], $loader, null, null, self::class);
	}

	private function getObjectStoreRootMount(IStorageFactory $loader, IObjectStore $objectStore): MountPoint {
		$arguments = array_merge($this->objectStoreConfig->getObjectStoreArgumentsForRoot(), [
			'objectstore' => $objectStore,
		]);

		return new MountPoint(ObjectStoreStorage::class, '/', $arguments, $loader, null, null, self::class);
	}
}
