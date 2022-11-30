<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Mount;

use OC;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\Storage\LocalRootStorage;
use OCP\Files\Config\IRootMountProvider;
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
		$objectStoreConfig = $this->objectStoreConfig->getObjectStoreConfigForRoot();

		if ($objectStoreConfig) {
			return [$this->getObjectStoreRootMount($loader, $objectStoreConfig)];
		} else {
			return [$this->getLocalRootMount($loader)];
		}
	}

	private function getLocalRootMount(IStorageFactory $loader): MountPoint {
		$configDataDirectory = $this->config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data');
		return new MountPoint(LocalRootStorage::class, '/', ['datadir' => $configDataDirectory], $loader, null, null, self::class);
	}

	private function getObjectStoreRootMount(IStorageFactory $loader, array $objectStoreConfig): MountPoint {
		$arguments = array_merge($objectStoreConfig['arguments'], [
			'objectstore' => $this->objectStoreConfig->buildObjectStore($objectStoreConfig),
		]);

		return new MountPoint(ObjectStoreStorage::class, '/', $arguments, $loader, null, null, self::class);
	}
}
