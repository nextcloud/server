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
		$config = $this->getObjectStoreConfig();

		if ($config && $config['arguments']['multibucket']) {
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
		$config = $this->getObjectStoreConfig();

		if ($config && $config['arguments']['multibucket']) {
			$config['arguments']['bucket'] = $this->getBucketForUser($user, $config);
		}
		return $config;
	}

	/**
	 * @return ?ObjectStoreConfig
	 */
	private function getObjectStoreConfig(): ?array {
		$objectStore = $this->config->getSystemValue('objectstore', null);
		$objectStoreMultiBucket = $this->config->getSystemValue('objectstore_multibucket', null);

		// new-style multibucket config uses the same 'objectstore' key but sets `'multibucket' => true`, transparently upgrade older style config
		if ($objectStoreMultiBucket) {
			$objectStoreMultiBucket['arguments']['multibucket'] = true;
			return $this->validateObjectStoreConfig($objectStoreMultiBucket);
		} elseif ($objectStore) {
			return $this->validateObjectStoreConfig($objectStore);
		} else {
			return null;
		}
	}

	/**
	 * @return ObjectStoreConfig
	 */
	private function validateObjectStoreConfig(array $config) {
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
