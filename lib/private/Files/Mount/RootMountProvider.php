<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Mount;

use OC;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\Storage\LocalRootStorage;
use OC_App;
use OCP\Files\Config\IRootMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class RootMountProvider implements IRootMountProvider {
	private IConfig $config;
	private LoggerInterface $logger;

	public function __construct(IConfig $config, LoggerInterface $logger) {
		$this->config = $config;
		$this->logger = $logger;
	}

	public function getRootMounts(IStorageFactory $loader): array {
		$objectStore = $this->config->getSystemValue('objectstore', null);
		$objectStoreMultiBucket = $this->config->getSystemValue('objectstore_multibucket', null);

		if ($objectStoreMultiBucket) {
			return [$this->getMultiBucketStoreRootMount($loader, $objectStoreMultiBucket)];
		} elseif ($objectStore) {
			return [$this->getObjectStoreRootMount($loader, $objectStore)];
		} else {
			return [$this->getLocalRootMount($loader)];
		}
	}

	private function validateObjectStoreConfig(array &$config) {
		if (empty($config['class'])) {
			$this->logger->error('No class given for objectstore', ['app' => 'files']);
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = [];
		}

		// instantiate object store implementation
		$name = $config['class'];
		if (str_starts_with($name, 'OCA\\') && substr_count($name, '\\') >= 2) {
			$segments = explode('\\', $name);
			OC_App::loadApp(strtolower($segments[1]));
		}
	}

	private function getLocalRootMount(IStorageFactory $loader): MountPoint {
		$configDataDirectory = $this->config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data');
		return new MountPoint(LocalRootStorage::class, '/', ['datadir' => $configDataDirectory], $loader, null, null, self::class);
	}

	private function getObjectStoreRootMount(IStorageFactory $loader, array $config): MountPoint {
		$this->validateObjectStoreConfig($config);

		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);
		// mount with plain / root object store implementation
		$config['class'] = ObjectStoreStorage::class;

		return new MountPoint($config['class'], '/', $config['arguments'], $loader, null, null, self::class);
	}

	private function getMultiBucketStoreRootMount(IStorageFactory $loader, array $config): MountPoint {
		$this->validateObjectStoreConfig($config);

		if (!isset($config['arguments']['bucket'])) {
			$config['arguments']['bucket'] = '';
		}
		// put the root FS always in first bucket for multibucket configuration
		$config['arguments']['bucket'] .= '0';

		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);
		// mount with plain / root object store implementation
		$config['class'] = ObjectStoreStorage::class;

		return new MountPoint($config['class'], '/', $config['arguments'], $loader, null, null, self::class);
	}
}
