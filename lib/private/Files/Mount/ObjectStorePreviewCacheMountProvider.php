<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Mount;

use OC\Files\ObjectStore\AppdataPreviewObjectStoreStorage;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\Storage\Wrapper\Jail;
use OCP\Files\Config\IRootMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Mount provider for object store app data folder for previews
 */
class ObjectStorePreviewCacheMountProvider implements IRootMountProvider {
	private LoggerInterface $logger;
	/** @var IConfig */
	private $config;

	public function __construct(LoggerInterface $logger, IConfig $config) {
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * @return MountPoint[]
	 * @throws \Exception
	 */
	public function getRootMounts(IStorageFactory $loader): array {
		if (!is_array($this->config->getSystemValue('objectstore_multibucket'))) {
			return [];
		}
		if ($this->config->getSystemValue('objectstore.multibucket.preview-distribution', false) !== true) {
			return [];
		}

		$instanceId = $this->config->getSystemValueString('instanceid', '');
		$mountPoints = [];
		$directoryRange = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];
		$i = 0;
		foreach ($directoryRange as $parent) {
			foreach ($directoryRange as $child) {
				$mountPoints[] = new MountPoint(
					AppdataPreviewObjectStoreStorage::class,
					'/appdata_' . $instanceId . '/preview/' . $parent . '/' . $child,
					$this->getMultiBucketObjectStore($i),
					$loader,
					null,
					null,
					self::class
				);
				$i++;
			}
		}

		$rootStorageArguments = $this->getMultiBucketObjectStoreForRoot();
		$fakeRootStorage = new ObjectStoreStorage($rootStorageArguments);
		$fakeRootStorageJail = new Jail([
			'storage' => $fakeRootStorage,
			'root' => '/appdata_' . $instanceId . '/preview',
		]);

		// add a fallback location to be able to fetch existing previews from the old bucket
		$mountPoints[] = new MountPoint(
			$fakeRootStorageJail,
			'/appdata_' . $instanceId . '/preview/old-multibucket',
			null,
			$loader,
			null,
			null,
			self::class
		);

		return $mountPoints;
	}

	protected function getMultiBucketObjectStore(int $number): array {
		$config = $this->config->getSystemValue('objectstore_multibucket');

		// sanity checks
		if (empty($config['class'])) {
			$this->logger->error('No class given for objectstore', ['app' => 'files']);
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = [];
		}

		/*
		 * Use any provided bucket argument as prefix
		 * and add the mapping from parent/child => bucket
		 */
		if (!isset($config['arguments']['bucket'])) {
			$config['arguments']['bucket'] = '';
		}

		$config['arguments']['bucket'] .= "-preview-$number";

		// instantiate object store implementation
		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);

		$config['arguments']['internal-id'] = $number;

		return $config['arguments'];
	}

	protected function getMultiBucketObjectStoreForRoot(): array {
		$config = $this->config->getSystemValue('objectstore_multibucket');

		// sanity checks
		if (empty($config['class'])) {
			$this->logger->error('No class given for objectstore', ['app' => 'files']);
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = [];
		}

		/*
		 * Use any provided bucket argument as prefix
		 * and add the mapping from parent/child => bucket
		 */
		if (!isset($config['arguments']['bucket'])) {
			$config['arguments']['bucket'] = '';
		}
		$config['arguments']['bucket'] .= '0';

		// instantiate object store implementation
		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);

		return $config['arguments'];
	}
}
