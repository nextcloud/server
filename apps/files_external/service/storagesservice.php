<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_external\Service;

use \OCP\IUserSession;
use \OC\Files\Filesystem;

use \OCA\Files_external\Lib\StorageConfig;
use \OCA\Files_external\NotFoundException;
use \OCA\Files_External\Service\BackendService;

/**
 * Service class to manage external storages
 */
abstract class StoragesService {

	/** @var BackendService */
	protected $backendService;

	/**
	 * @param BackendService $backendService
	 */
	public function __construct(BackendService $backendService) {
		$this->backendService = $backendService;
	}

	/**
	 * Read legacy config data
	 *
	 * @return array list of mount configs
	 */
	protected function readLegacyConfig() {
		// read global config
		return \OC_Mount_Config::readData();
	}

	/**
	 * Copy legacy storage options into the given storage config object.
	 *
	 * @param StorageConfig $storageConfig storage config to populate
	 * @param string $mountType mount type
	 * @param string $applicable applicable user or group
	 * @param array $storageOptions legacy storage options
	 *
	 * @return StorageConfig populated storage config
	 */
	protected function populateStorageConfigWithLegacyOptions(
		&$storageConfig,
		$mountType,
		$applicable,
		$storageOptions
	) {
		$backend = $this->backendService->getBackend($storageOptions['class']);
		$storageConfig->setBackend($backend);

		$storageConfig->setBackendOptions($storageOptions['options']);
		if (isset($storageOptions['mountOptions'])) {
			$storageConfig->setMountOptions($storageOptions['mountOptions']);
		}
		if (!isset($storageOptions['priority'])) {
			$storageOptions['priority'] = $backend->getPriority();
		}
		$storageConfig->setPriority($storageOptions['priority']);

		if ($mountType === \OC_Mount_Config::MOUNT_TYPE_USER) {
			$applicableUsers = $storageConfig->getApplicableUsers();
			if ($applicable !== 'all') {
				$applicableUsers[] = $applicable;
				$storageConfig->setApplicableUsers($applicableUsers);
			}
		} else if ($mountType === \OC_Mount_Config::MOUNT_TYPE_GROUP) {
			$applicableGroups = $storageConfig->getApplicableGroups();
			$applicableGroups[] = $applicable;
			$storageConfig->setApplicableGroups($applicableGroups);
		}
		return $storageConfig;
	}

	/**
	 * Read the external storages config
	 *
	 * @return array map of storage id to storage config
	 */
	protected function readConfig() {
		$mountPoints = $this->readLegacyConfig();

		/**
		 * Here is the how the horribly messy mount point array looks like
		 * from the mount.json file:
		 *
		 * $storageOptions = $mountPoints[$mountType][$applicable][$mountPath]
		 *
		 * - $mountType is either "user" or "group"
		 * - $applicable is the name of a user or group (or the current user for personal mounts)
		 * - $mountPath is the mount point path (where the storage must be mounted)
		 * - $storageOptions is a map of storage options:
		 *     - "priority": storage priority
		 *     - "backend": backend class name
		 *     - "options": backend-specific options
		 *     - "mountOptions": mount-specific options (ex: disable previews, scanner, etc)
		 */

		// group by storage id
		$storages = [];

		// for storages without id (legacy), group by config hash for
		// later processing
		$storagesWithConfigHash = [];

		foreach ($mountPoints as $mountType => $applicables) {
			foreach ($applicables as $applicable => $mountPaths) {
				foreach ($mountPaths as $rootMountPath => $storageOptions) {
					$currentStorage = null;

					/**
					 * Flag whether the config that was read already has an id.
					 * If not, it will use a config hash instead and generate
					 * a proper id later
					 *
					 * @var boolean
					 */
					$hasId = false;

					// the root mount point is in the format "/$user/files/the/mount/point"
					// we remove the "/$user/files" prefix
					$parts = explode('/', trim($rootMountPath, '/'), 3);
					if (count($parts) < 3) {
						// something went wrong, skip
						\OCP\Util::writeLog(
							'files_external',
							'Could not parse mount point "' . $rootMountPath . '"',
							\OCP\Util::ERROR
						);
						continue;
					}

					$relativeMountPath = $parts[2];

					// note: we cannot do this after the loop because the decrypted config
					// options might be needed for the config hash
					$storageOptions['options'] = \OC_Mount_Config::decryptPasswords($storageOptions['options']);

					if (isset($storageOptions['id'])) {
						$configId = (int)$storageOptions['id'];
						if (isset($storages[$configId])) {
							$currentStorage = $storages[$configId];
						}
						$hasId = true;
					} else {
						// missing id in legacy config, need to generate
						// but at this point we don't know the max-id, so use
						// first group it by config hash
						$storageOptions['mountpoint'] = $rootMountPath;
						$configId = \OC_Mount_Config::makeConfigHash($storageOptions);
						if (isset($storagesWithConfigHash[$configId])) {
							$currentStorage = $storagesWithConfigHash[$configId];
						}
					}

					if (is_null($currentStorage)) {
						// create new
						$currentStorage = new StorageConfig($configId);
						$currentStorage->setMountPoint($relativeMountPath);
					}

					$this->populateStorageConfigWithLegacyOptions(
						$currentStorage,
						$mountType,
						$applicable,
						$storageOptions
					);

					if ($hasId) {
						$storages[$configId] = $currentStorage;
					} else {
						$storagesWithConfigHash[$configId] = $currentStorage;
					}
				}
			}
		}

		// process storages with config hash, they must get a real id
		if (!empty($storagesWithConfigHash)) {
			$nextId = $this->generateNextId($storages);
			foreach ($storagesWithConfigHash as $storage) {
				$storage->setId($nextId);
				$storages[$nextId] = $storage;
				$nextId++;
			}

			// re-save the config with the generated ids
			$this->writeConfig($storages);
		}

		return $storages;
	}

	/**
	 * Add mount point into the messy mount point structure
	 *
	 * @param array $mountPoints messy array of mount points
	 * @param string $mountType mount type
	 * @param string $applicable single applicable user or group
	 * @param string $rootMountPoint root mount point to use
	 * @param array $storageConfig storage config to set to the mount point
	 */
	protected function addMountPoint(&$mountPoints, $mountType, $applicable, $rootMountPoint, $storageConfig) {
		if (!isset($mountPoints[$mountType])) {
			$mountPoints[$mountType] = [];
		}

		if (!isset($mountPoints[$mountType][$applicable])) {
			$mountPoints[$mountType][$applicable] = [];
		}

		$options = [
			'id' => $storageConfig->getId(),
			'class' => $storageConfig->getBackend()->getClass(),
			'options' => $storageConfig->getBackendOptions(),
		];

		if (!is_null($storageConfig->getPriority())) {
			$options['priority'] = $storageConfig->getPriority();
		}

		$mountOptions = $storageConfig->getMountOptions();
		if (!empty($mountOptions)) {
			$options['mountOptions'] = $mountOptions;
		}

		$mountPoints[$mountType][$applicable][$rootMountPoint] = $options;
	}

	/**
	 * Write the storages to the configuration.
	 *
	 * @param array $storages map of storage id to storage config
	 */
	abstract protected function writeConfig($storages);

	/**
	 * Get a storage with status
	 *
	 * @param int $id storage id
	 *
	 * @return StorageConfig
	 * @throws NotFoundException if the storage with the given id was not found
	 */
	public function getStorage($id) {
		$allStorages = $this->readConfig();

		if (!isset($allStorages[$id])) {
			throw new NotFoundException('Storage with id "' . $id . '" not found');
		}

		return $allStorages[$id];
	}

	/**
	 * Gets all storages
	 *
	 * @return array array of storage configs
	 */
	public function getAllStorages() {
		return $this->readConfig();
	}

	/**
	 * Add new storage to the configuration
	 *
	 * @param array $newStorage storage attributes
	 *
	 * @return StorageConfig storage config, with added id
	 */
	public function addStorage(StorageConfig $newStorage) {
		$allStorages = $this->readConfig();

		$configId = $this->generateNextId($allStorages);
		$newStorage->setId($configId);

		// add new storage
		$allStorages[$configId] = $newStorage;

		$this->writeConfig($allStorages);

		$this->triggerHooks($newStorage, Filesystem::signal_create_mount);

		$newStorage->setStatus(\OC_Mount_Config::STATUS_SUCCESS);
		return $newStorage;
	}

	/**
	 * Create a storage from its parameters
	 *
	 * @param string $mountPoint storage mount point
	 * @param string $backendClass backend class name
	 * @param array $backendOptions backend-specific options
	 * @param array|null $mountOptions mount-specific options
	 * @param array|null $applicableUsers users for which to mount the storage
	 * @param array|null $applicableGroups groups for which to mount the storage
	 * @param int|null $priority priority
	 *
	 * @return StorageConfig
	 */
	public function createStorage(
		$mountPoint,
		$backendClass,
		$backendOptions,
		$mountOptions = null,
		$applicableUsers = null,
		$applicableGroups = null,
		$priority = null
	) {
		$backend = $this->backendService->getBackend($backendClass);
		if (!$backend) {
			throw new \InvalidArgumentException('Unable to get backend for backend class '.$backendClass);
		}
		$newStorage = new StorageConfig();
		$newStorage->setMountPoint($mountPoint);
		$newStorage->setBackend($backend);
		$newStorage->setBackendOptions($backendOptions);
		if (isset($mountOptions)) {
			$newStorage->setMountOptions($mountOptions);
		}
		if (isset($applicableUsers)) {
			$newStorage->setApplicableUsers($applicableUsers);
		}
		if (isset($applicableGroups)) {
			$newStorage->setApplicableGroups($applicableGroups);
		}
		if (isset($priority)) {
			$newStorage->setPriority($priority);
		}

		return $newStorage;
	}

	/**
	 * Triggers the given hook signal for all the applicables given
	 *
	 * @param string $signal signal
	 * @param string $mountPoint hook mount pount param
	 * @param string $mountType hook mount type param
	 * @param array $applicableArray array of applicable users/groups for which to trigger the hook
	 */
	protected function triggerApplicableHooks($signal, $mountPoint, $mountType, $applicableArray) {
		foreach ($applicableArray as $applicable) {
			\OC_Hook::emit(
				Filesystem::CLASSNAME,
				$signal,
				[
					Filesystem::signal_param_path => $mountPoint,
					Filesystem::signal_param_mount_type => $mountType,
					Filesystem::signal_param_users => $applicable,
				]
			);
		}
	}

	/**
	 * Triggers $signal for all applicable users of the given
	 * storage
	 *
	 * @param StorageConfig $storage storage data
	 * @param string $signal signal to trigger
	 */
	abstract protected function triggerHooks(StorageConfig $storage, $signal);

	/**
	 * Triggers signal_create_mount or signal_delete_mount to
	 * accomodate for additions/deletions in applicableUsers
	 * and applicableGroups fields.
	 *
	 * @param StorageConfig $oldStorage old storage data
	 * @param StorageConfig $newStorage new storage data
	 */
	abstract protected function triggerChangeHooks(StorageConfig $oldStorage, StorageConfig $newStorage);

	/**
	 * Update storage to the configuration
	 *
	 * @param StorageConfig $updatedStorage storage attributes
	 *
	 * @return StorageConfig storage config
	 * @throws NotFoundException if the given storage does not exist in the config
	 */
	public function updateStorage(StorageConfig $updatedStorage) {
		$allStorages = $this->readConfig();

		$id = $updatedStorage->getId();
		if (!isset($allStorages[$id])) {
			throw new NotFoundException('Storage with id "' . $id . '" not found');
		}

		$oldStorage = $allStorages[$id];
		$allStorages[$id] = $updatedStorage;

		$this->writeConfig($allStorages);

		$this->triggerChangeHooks($oldStorage, $updatedStorage);

		return $this->getStorage($id);
	}

	/**
	 * Delete the storage with the given id.
	 *
	 * @param int $id storage id
	 *
	 * @throws NotFoundException if no storage was found with the given id
	 */
	public function removeStorage($id) {
		$allStorages = $this->readConfig();

		if (!isset($allStorages[$id])) {
			throw new NotFoundException('Storage with id "' . $id . '" not found');
		}

		$deletedStorage = $allStorages[$id];
		unset($allStorages[$id]);

		$this->writeConfig($allStorages);

		$this->triggerHooks($deletedStorage, Filesystem::signal_delete_mount);
	}

	/**
	 * Generates a configuration id to use for a new configuration entry.
	 *
	 * @param array $allStorages array of all storage configs
	 *
	 * @return int id
	 */
	protected function generateNextId($allStorages) {
		if (empty($allStorages)) {
			return 1;
		}
		// note: this will mess up with with concurrency,
		// but so did the mount.json. This horribly hack
		// will disappear once we move to DB tables to
		// store the config
		return (max(array_keys($allStorages)) + 1);
	}

}
