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
use OCP\Files\Config\IAuthoritativeMountProvider;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IPartialMountProvider;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\Storage\IConstructableStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IStorageFactory;
use OCP\Files\StorageNotAvailableException;
use OCP\IUser;
use OCP\Server;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Make the old files_external config work with the new public mount config api
 */
class ConfigAdapter implements IMountProvider, IAuthoritativeMountProvider, IPartialMountProvider {
	public function __construct(
		private UserStoragesService $userStoragesService,
		private UserGlobalStoragesService $userGlobalStoragesService,
		private ClockInterface $clock,
	) {
	}

	/**
	 * @param class-string $class
	 * @return class-string<IObjectStore>
	 * @throws \InvalidArgumentException
	 * @psalm-taint-escape callable
	 */
	private function validateObjectStoreClassString(string $class): string {
		if (!\is_subclass_of($class, IObjectStore::class)) {
			throw new \InvalidArgumentException('Invalid object store');
		}
		return $class;
	}

	/**
	 * Process storage ready for mounting
	 *
	 * @throws ContainerExceptionInterface
	 */
	private function prepareStorageConfig(StorageConfig &$storage, IUser $user): void {
		foreach ($storage->getBackendOptions() as $option => $value) {
			$storage->setBackendOption($option, MountConfig::substitutePlaceholdersInConfig($value, $user->getUID()));
		}

		$objectStore = $storage->getBackendOption('objectstore');
		if ($objectStore) {
			$objectClass = $this->validateObjectStoreClassString($objectStore['class']);
			$storage->setBackendOption('objectstore', new $objectClass($objectStore));
		}

		$storage->getAuthMechanism()->manipulateStorageConfig($storage, $user);
		$storage->getBackend()->manipulateStorageConfig($storage, $user);
	}

	public function constructStorageForUser(IUser $user, StorageConfig $storage) {
		$this->prepareStorageConfig($storage, $user);
		return $this->constructStorage($storage);
	}

	/**
	 * Construct the storage implementation
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
	 * @param list<StorageConfig> $storageConfigs
	 * @return array
	 * @throws ContainerExceptionInterface
	 */
	private function getAvailableStorages(array $storageConfigs, IUser $user): array {
		$storages = array_map(function (StorageConfig $storageConfig) use ($user): IStorage {
			try {
				return $this->constructStorageForUser($user, $storageConfig);
			} catch (\Exception $e) {
				// propagate exception into filesystem
				return new FailedStorage(['exception' => $e]);
			}
		}, $storageConfigs);


		Storage::getGlobalCache()->loadForStorageIds(array_map(function (IStorage $storage) {
			return $storage->getId();
		}, $storages));

		return array_map(function (IStorage $storage, StorageConfig $storageConfig): IStorage {
			try {
				$availability = $storage->getAvailability();
				if (!$availability['available'] && !Availability::shouldRecheck($availability)) {
					$storage = new FailedStorage([
						'exception' => new StorageNotAvailableException('Storage with mount id ' . $storageConfig->getId() . ' is not available'),
					]);
				}
			} catch (\Exception $e) {
				// propagate exception into filesystem
				$storage = new FailedStorage(['exception' => $e]);
			}
			return $storage;
		}, $storages, $storageConfigs);
	}

	#[Override]
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		$this->userStoragesService->setUser($user);
		$this->userGlobalStoragesService->setUser($user);

		$storageConfigs = $this->userGlobalStoragesService->getAllStoragesForUser();
		$availableStorages = $this->getAvailableStorages($storageConfigs, $user);

		$mounts = array_map(function (StorageConfig $storageConfig, IStorage $storage) use ($user, $loader) {
			$mountpoint = '/' . $user->getUID() . '/files' . $storageConfig->getMountPoint();
			return $this->storageConfigToMount($user, $mountpoint, $loader, $storage, $storageConfig);
		}, $storageConfigs, $availableStorages);

		$this->userStoragesService->resetUser();
		$this->userGlobalStoragesService->resetUser();

		return $mounts;
	}

	#[Override]
	public function getMountsForPath(string $setupPathHint, bool $forChildren, array $mountProviderArgs, IStorageFactory $loader): array {
		$user = $mountProviderArgs[0]->mountInfo->getUser();

		if (!$forChildren) {
			// override path with mount point when fetching without children
			$setupPathHint = $mountProviderArgs[0]->mountInfo->getMountPoint();
		}

		$this->userStoragesService->setUser($user);
		$this->userGlobalStoragesService->setUser($user);

		$storageConfigs = $this->userGlobalStoragesService->getAllStoragesForUserWithPath($setupPathHint, $forChildren);
		$availableStorages = $this->getAvailableStorages($storageConfigs, $user);

		$mounts = [];

		$i = 0;
		foreach ($storageConfigs as $storageConfig) {
			$storage = $availableStorages[$i];
			$i++;
			$mountPoint = '/' . $user->getUID() . '/files' . $storageConfig->getMountPoint();
			$mounts[$mountPoint] = $this->storageConfigToMount($user, $mountPoint, $loader, $storage, $storageConfig);
		}

		$this->userStoragesService->resetUser();
		$this->userGlobalStoragesService->resetUser();

		return $mounts;
	}

	private function storageConfigToMount(IUser $user, string $mountPoint, IStorageFactory $loader, IStorage $storage, StorageConfig $storageConfig): IMountPoint {
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
				$mountPoint,
				null,
				$loader,
				$storageConfig->getMountOptions(),
				$storageConfig->getId()
			);
		} else {
			return new SystemMountPoint(
				$storageConfig,
				$storage,
				$mountPoint,
				null,
				$loader,
				$storageConfig->getMountOptions(),
				$storageConfig->getId()
			);
		}
	}
}
