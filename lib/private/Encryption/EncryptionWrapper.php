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
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Files\Storage\IStorage;
use OCP\IAppConfig;
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
			if ($mount instanceof HomeMountPoint && !$this->shouldEncryptHomeStorage()) {
				return $storage;
			}
		}

		// Apply encryption wrapper
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
	}

	private function shouldEncryptHomeStorage(): bool {
		$appConfig = Server::get(IAppConfig::class);
		try {
			return $appConfig->getValueBool('encryption', 'encryptHomeStorage', true);
		} catch (AppConfigTypeConflictException) {
			// Stored as VALUE_STRING from a pre-upgrade installation.
			// RetypeEncryptionConfigKeys repair step will fix the type on occ upgrade.
			return $this->parseLegacyBoolString(
				$appConfig->getValueString('encryption', 'encryptHomeStorage', '1')
			);
		} catch (\Throwable) {
			// DB not ready (e.g. oc_appconfig does not yet exist during install).
			return true;
		}
	}

	private function parseLegacyBoolString(string $value): bool {
		return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
	}
}
