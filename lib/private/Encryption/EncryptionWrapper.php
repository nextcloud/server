<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Encryption;

use OC\Files\Filesystem;
use OC\Files\Mount\HomeMountPoint;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OC\Memcache\ArrayCache;
use OCP\Encryption\IFile;
use OCP\Encryption\Keys\IStorage as EncryptionKeysStorage;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Files\Storage\IStorage;
use Psr\Log\LoggerInterface;

/**
 * Class EncryptionWrapper
 *
 * applies the encryption storage wrapper
 *
 * @package OC\Encryption
 */
class EncryptionWrapper {
	/** @var ArrayCache */
	private $arrayCache;

	/** @var Manager */
	private $manager;

	private LoggerInterface $logger;

	/**
	 * EncryptionWrapper constructor.
	 */
	public function __construct(ArrayCache $arrayCache,
		Manager $manager,
		LoggerInterface $logger,
	) {
		$this->arrayCache = $arrayCache;
		$this->manager = $manager;
		$this->logger = $logger;
	}

	/**
	 * Wraps the given storage when it is not a shared storage
	 *
	 * @param string $mountPoint
	 * @param IStorage $storage
	 * @param IMountPoint $mount
	 * @param bool $force apply the wrapper even if the storage normally has encryption disabled, helpful for repair steps
	 * @return Encryption|IStorage
	 */
	public function wrapStorage(string $mountPoint, IStorage $storage, IMountPoint $mount, bool $force = false) {
		$parameters = [
			'storage' => $storage,
			'mountPoint' => $mountPoint,
			'mount' => $mount
		];

		// Only evaluate other conditions if not forced
		if (!$force) {
			// If a disabled storage medium, return basic storage
			if ($storage->instanceOfStorage(IDisableEncryptionStorage::class)) {
				return $storage;
			}

			// Root mount point handling: skip encryption wrapper
			if ($mountPoint === '/') {
				return $storage;
			}

			// Skip encryption for home mounts if encryptHomeStorage is disabled
			if ($mount instanceof HomeMountPoint
				&& \OC::$server->getConfig()->getAppValue('encryption', 'encryptHomeStorage', '1') !== '1') {
				return $storage;
			}
		}

		// Apply encryption wrapper
		$user = \OC::$server->getUserSession()->getUser();
		$mountManager = Filesystem::getMountManager();
		$uid = $user ? $user->getUID() : null;
		$fileHelper = \OC::$server->get(IFile::class);
		$keyStorage = \OC::$server->get(EncryptionKeysStorage::class);

		$util = new Util(
			new View(),
			\OC::$server->getUserManager(),
			\OC::$server->getGroupManager(),
			\OC::$server->getConfig()
		);
		return new Encryption(
			$parameters,
			$this->manager,
			$util,
			$this->logger,
			$fileHelper,
			$uid,
			$keyStorage,
			$mountManager,
			$this->arrayCache
		);
	}
}
