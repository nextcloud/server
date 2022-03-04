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
		if (strpos($name, 'OCA\\') === 0 && substr_count($name, '\\') >= 2) {
			$segments = explode('\\', $name);
			OC_App::loadApp(strtolower($segments[1]));
		}
	}

	private function getLocalRootMount(IStorageFactory $loader): MountPoint {
		$configDataDirectory = $this->config->getSystemValue("datadirectory", OC::$SERVERROOT . "/data");
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
