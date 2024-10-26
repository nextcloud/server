<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Config;

use OC\Files\Cache\Storage;
use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Wrapper\Availability;
use OC\Files\Storage\Wrapper\KnownMtime;
use OCA\Files_External\Lib\PersonalMount;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\MountConfig;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\AppFramework\QueryException;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\Storage\IConstructableStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IStorageFactory;
use OCP\Files\StorageNotAvailableException;
use OCP\IUser;
use OCP\Server;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Make the old files_external config work with the new public mount config api
 */
class ConfigAdapter implements IMountProvider {
	public function __construct(
		private UserStoragesService $userStoragesService,
		private UserGlobalStoragesService $userGlobalStoragesService,
		private ClockInterface $clock,
	) {
	}

	/**
	 * Process storage ready for mounting
	 *
	 * @throws QueryException
	 */
	private function prepareStorageConfig(StorageConfig &$storage, IUser $user): void {
		foreach ($storage->getBackendOptions() as $option => $value) {
			$storage->setBackendOption($option, MountConfig::substitutePlaceholdersInConfig($value, $user->getUID()));
		}

		$objectStore = $storage->getBackendOption('objectstore');
		if ($objectStore) {
			$objectClass = $objectStore['class'];
			if (!is_subclass_of($objectClass, IObjectStore::class)) {
				throw new \InvalidArgumentException('Invalid object store');
			}
			$storage->setBackendOption('objectstore', new $objectClass($objectStore));
		}

		$storage->getAuthMechanism()->manipulateStorageConfig($storage, $user);
		$storage->getBackend()->manipulateStorageConfig($storage, $user);
	}

	/**
	 * Construct the storage implementation
	 *
	 * @param StorageConfig $storageConfig
	 */
	private function constructStorage(StorageConfig $storageConfig): IStorage {
		$class = $storageConfig->getBackend()->getStorageClass();
		if (!is_a($class, IConstructableStorage::class, true)) {
			Server::get(LoggerInterface::class)->warning('Building a storage not implementing IConstructableStorage is deprecated since 31.0.0', ['class' => $class]);
		}
		$storage = new $class($storageConfig->getBackendOptions());

		// auth mechanism should fire first
		$storage = $storageConfig->getBackend()->wrapStorage($storage);
		$storage = $storageConfig->getAuthMechanism()->wrapStorage($storage);

		return $storage;
	}

	/**
	 * Get all mountpoints applicable for the user
	 *
	 * @return IMountPoint[]
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader) {
		$this->userStoragesService->setUser($user);
		$this->userGlobalStoragesService->setUser($user);

		$storageConfigs = $this->userGlobalStoragesService->getAllStoragesForUser();

		$storages = array_map(function (StorageConfig $storageConfig) use ($user) {
			try {
				$this->prepareStorageConfig($storageConfig, $user);
				return $this->constructStorage($storageConfig);
			} catch (\Exception $e) {
				// propagate exception into filesystem
				return new FailedStorage(['exception' => $e]);
			}
		}, $storageConfigs);


		Storage::getGlobalCache()->loadForStorageIds(array_map(function (IStorage $storage) {
			return $storage->getId();
		}, $storages));

		$availableStorages = array_map(function (IStorage $storage, StorageConfig $storageConfig): IStorage {
			try {
				$availability = $storage->getAvailability();
				if (!$availability['available'] && !Availability::shouldRecheck($availability)) {
					$storage = new FailedStorage([
						'exception' => new StorageNotAvailableException('Storage with mount id ' . $storageConfig->getId() . ' is not available')
					]);
				}
			} catch (\Exception $e) {
				// propagate exception into filesystem
				$storage = new FailedStorage(['exception' => $e]);
			}
			return $storage;
		}, $storages, $storageConfigs);

		$mounts = array_map(function (StorageConfig $storageConfig, IStorage $storage) use ($user, $loader) {
			$storage->setOwner($user->getUID());
			if ($storageConfig->getType() === StorageConfig::MOUNT_TYPE_PERSONAL) {
				return new PersonalMount(
					$this->userStoragesService,
					$storageConfig,
					$storageConfig->getId(),
					new KnownMtime([
						'storage' => $storage,
						'clock' => $this->clock,
					]),
					'/' . $user->getUID() . '/files' . $storageConfig->getMountPoint(),
					null,
					$loader,
					$storageConfig->getMountOptions(),
					$storageConfig->getId()
				);
			} else {
				return new SystemMountPoint(
					$storageConfig,
					$storage,
					'/' . $user->getUID() . '/files' . $storageConfig->getMountPoint(),
					null,
					$loader,
					$storageConfig->getMountOptions(),
					$storageConfig->getId()
				);
			}
		}, $storageConfigs, $availableStorages);

		$this->userStoragesService->resetUser();
		$this->userGlobalStoragesService->resetUser();

		return $mounts;
	}
}
