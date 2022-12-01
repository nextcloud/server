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

namespace OC\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;
use OCP\IConfig;
use OCP\IUser;
use Psr\Log\LoggerInterface;

class PrimaryObjectStoreConfig {
	private IConfig $config;
	private LoggerInterface $logger;

	public function __construct(IConfig $config, LoggerInterface $logger) {
		$this->config = $config;
		$this->logger = $logger;
	}

	public function getObjectStoreForRoot(): ?IObjectStore {
		$config = $this->getObjectStoreConfigForRoot();
		if (!$config) {
			return null;
		}

		if ($config['multibucket']) {
			if (!isset($config['arguments']['bucket'])) {
				$config['arguments']['bucket'] = '';
			}

			// put the root FS always in first bucket for multibucket configuration
			$config['arguments']['bucket'] .= '0';
		}

		return new $config['class']($config['arguments']);
	}

	public function getObjectStoreForUser(IUser $user): ?IObjectStore {
		$config = $this->getObjectStoreConfigForUser($user);
		if (!$config) {
			return null;
		}

		if ($config['arguments']['multibucket']) {
			$config['arguments']['bucket'] = $this->getBucketForUser($user, $config);
		}

		// instantiate object store implementation
		return new $config['class']($config['arguments']);
	}

	public function getObjectStoreArgumentsForRoot(): array {
		$config = $this->getObjectStoreConfigForRoot();
		if ($config === null) {
			return [];
		}
		return $config['arguments'] ?? [];
	}

	public function getObjectStoreArgumentsForUser(IUser $user): array {
		$config = $this->getObjectStoreConfigForUser($user);
		if ($config === null) {
			return [];
		}
		return $config['arguments'] ?? [];
	}

	private function getObjectStoreConfigForRoot(): ?array {
		$configs = $this->getObjectStoreConfig();

		return $configs['root'] ?? $configs['default'];
	}

	private function getObjectStoreConfigForUser(IUser $user): ?array {
		$configs = $this->getObjectStoreConfig();
		$store = $this->config->getUserValue($user->getUID(), 'homeobjectstore', 'objectstore', null);

		if ($store) {
			return $configs[$store];
		} else {
			return $configs['default'];
		}
	}

	private function getObjectStoreConfig(): ?array {
		$objectStore = $this->config->getSystemValue('objectstore', null);
		$objectStoreMultiBucket = $this->config->getSystemValue('objectstore_multibucket', null);

		// new-style multibucket config uses the same 'objectstore' key but sets `'multibucket' => true`, transparently upgrade older style config
		if ($objectStoreMultiBucket) {
			$objectStoreMultiBucket['multibucket'] = true;
			$objectStore = [
				'default' => $objectStoreMultiBucket,
			];
		}
		if ($objectStore === null) {
			return ['default' => null];
		}

		if (!isset($objectStore['default'])) {
			$objectStore = [
				'default' => $objectStore,
			];
		}

		foreach ($objectStore as &$config) {
			if (!isset($config['multibucket'])) {
				$config['multibucket'] = false;
			}

			$this->validateObjectStoreConfig($config);
		}

		return $objectStore;
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
			\OC_App::loadApp(strtolower($segments[1]));
		}
	}

	private function getBucketForUser(IUser $user, array $config): string {
		$bucket = $this->config->getUserValue($user->getUID(), 'homeobjectstore', 'bucket', null);

		if ($bucket === null) {
			/*
			 * Use any provided bucket argument as prefix
			 * and add the mapping from username => bucket
			 */
			if (!isset($config['arguments']['bucket'])) {
				$config['arguments']['bucket'] = '';
			}
			$mapper = new Mapper($user, $this->config);
			$numBuckets = isset($config['arguments']['num_buckets']) ? $config['arguments']['num_buckets'] : 64;
			$bucket = $config['arguments']['bucket'] . $mapper->getBucket($numBuckets);

			$this->config->setUserValue($user->getUID(), 'homeobjectstore', 'bucket', $bucket);
		}

		return $bucket;
	}
}
