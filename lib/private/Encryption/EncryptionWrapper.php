<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Encryption;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OC\Memcache\ArrayCache;
use OCP\Encryption\IFile;
use OCP\Encryption\Keys\IStorage as EncryptionKeysStorage;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Class EncryptionWrapper
 *
 * applies the encryption storage wrapper
 *
 * @package OC\Encryption
 */
class EncryptionWrapper {
	/**
	 * EncryptionWrapper constructor.
	 */
	public function __construct(
		private ArrayCache $arrayCache,
		private Manager $manager,
		private LoggerInterface $logger,
	) {
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

		if ($force || (!$storage->instanceOfStorage(IDisableEncryptionStorage::class) && $mountPoint !== '/')) {
			$user = Server::get(IUserSession::class)->getUser();
			$mountManager = Filesystem::getMountManager();
			$uid = $user ? $user->getUID() : null;
			$fileHelper = Server::get(IFile::class);
			$keyStorage = Server::get(EncryptionKeysStorage::class);

			$util = new Util(
				new View(),
				Server::get(IUserManager::class),
				Server::get(IGroupManager::class),
				Server::get(IConfig::class)
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
		} else {
			return $storage;
		}
	}
}
