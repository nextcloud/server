<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\ObjectStore;

use OCP\App\IAppManager;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\IConfig;
use OCP\IUser;

/**
 * @psalm-type ObjectStoreConfig array{class: class-string<IObjectStore>, arguments: array{multibucket: bool, ...}}
 */
class PrimaryObjectStoreConfig {
	public function __construct(
		private readonly IConfig $config,
		private readonly IAppManager $appManager,
	) {
	}

	/**
	 * @param ObjectStoreConfig $config
	 */
	public function buildObjectStore(array $config): IObjectStore {
		return new $config['class']($config['arguments']);
	}

	/**
	 * @return ?ObjectStoreConfig
	 */
	public function getObjectStoreConfigForRoot(): ?array {
		if (!$this->hasObjectStore()) {
			return null;
		}

		$config = $this->getObjectStoreConfiguration('root');

		if ($config['arguments']['multibucket']) {
			if (!isset($config['arguments']['bucket'])) {
				$config['arguments']['bucket'] = '';
			}

			// put the root FS always in first bucket for multibucket configuration
			$config['arguments']['bucket'] .= '0';
		}
		return $config;
	}

	/**
	 * @return ?ObjectStoreConfig
	 */
	public function getObjectStoreConfigForUser(IUser $user): ?array {
		if (!$this->hasObjectStore()) {
			return null;
		}

		$store = $this->getObjectStoreForUser($user);
		$config = $this->getObjectStoreConfiguration($store);

		if ($config['arguments']['multibucket']) {
			$config['arguments']['bucket'] = $this->getBucketForUser($user, $config);
		}
		return $config;
	}

	/**
	 * @param string $name
	 * @return ObjectStoreConfig
	 */
	private function getObjectStoreConfiguration(string $name): array {
		$configs = $this->getObjectStoreConfigs();
		$name = $this->resolveAlias($name);
		if (!isset($configs[$name])) {
			throw new \Exception("Object store configuration for '$name' not found");
		}
		return $configs[$name];
	}

	private function resolveAlias(string $name): string {
		$configs = $this->getObjectStoreConfigs();

		while (isset($configs[$name]) && is_string($configs[$name])) {
			$name = $configs[$name];
		}
		return $name;
	}

	private function hasObjectStore(): bool {
		$objectStore = $this->config->getSystemValue('objectstore', null);
		$objectStoreMultiBucket = $this->config->getSystemValue('objectstore_multibucket', null);
		return $objectStore || $objectStoreMultiBucket;
	}

	/**
	 * @return ?array<string, ObjectStoreConfig|string>
	 */
	public function getObjectStoreConfigs(): ?array {
		$objectStore = $this->config->getSystemValue('objectstore', null);
		$objectStoreMultiBucket = $this->config->getSystemValue('objectstore_multibucket', null);

		// new-style multibucket config uses the same 'objectstore' key but sets `'multibucket' => true`, transparently upgrade older style config
		if ($objectStoreMultiBucket) {
			$objectStoreMultiBucket['arguments']['multibucket'] = true;
			return [
				'default' => $this->validateObjectStoreConfig($objectStoreMultiBucket),
				'root' => 'default',
			];
		} elseif ($objectStore) {
			if (!isset($objectStore['default'])) {
				$objectStore = [
					'default' => $objectStore,
				];
			}
			if (!isset($objectStore['root'])) {
				$objectStore['root'] = 'default';
			}

			return array_map($this->validateObjectStoreConfig(...), $objectStore);
		} else {
			return null;
		}
	}

	/**
	 * @param string|array $config
	 * @return string|ObjectStoreConfig
	 */
	private function validateObjectStoreConfig($config) {
		if (is_string($config)) {
			return $config;
		}
		if (!isset($config['class'])) {
			throw new \Exception('No class configured for object store');
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = [];
		}
		$class = $config['class'];
		$arguments = $config['arguments'];
		if (!is_array($arguments)) {
			throw new \Exception('Configured object store arguments are not an array');
		}
		if (!isset($arguments['multibucket'])) {
			$arguments['multibucket'] = false;
		}
		if (!is_bool($arguments['multibucket'])) {
			throw new \Exception('arguments.multibucket must be a boolean in object store configuration');
		}

		if (!is_string($class)) {
			throw new \Exception('Configured class for object store is not a string');
		}

		if (str_starts_with($class, 'OCA\\') && substr_count($class, '\\') >= 2) {
			[$appId] = explode('\\', $class);
			$this->appManager->loadApp(strtolower($appId));
		}

		if (!is_a($class, IObjectStore::class, true)) {
			throw new \Exception('Configured class for object store is not an object store');
		}
		return [
			'class' => $class,
			'arguments' => $arguments,
		];
	}

	public function getBucketForUser(IUser $user, array $config): string {
		$bucket = $this->getSetBucketForUser($user);

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

	public function getSetBucketForUser(IUser $user): ?string {
		return $this->config->getUserValue($user->getUID(), 'homeobjectstore', 'bucket', null);
	}

	public function getObjectStoreForUser(IUser $user): string {
		$value = $this->config->getUserValue($user->getUID(), 'homeobjectstore', 'objectstore', null);
		if ($value === null) {
			$value = $this->resolveAlias('default');
			$this->config->setUserValue($user->getUID(), 'homeobjectstore', 'objectstore', $value);
		}
		return $value;
	}
}
