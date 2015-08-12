<?php
/**
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
use \OCA\Files_External\Service\UserTrait;

/**
 * Service class to manage user external storages
 * (aka personal storages)
 */
class UserStoragesService extends StoragesService {

	use UserTrait;

	/**
	 * Create a user storages service
	 *
	 * @param BackendService $backendService
	 * @param IUserSession $userSession user session
	 */
	public function __construct(
		BackendService $backendService,
		IUserSession $userSession
	) {
		$this->userSession = $userSession;
		parent::__construct($backendService);
	}

	/**
	 * Read legacy config data
	 *
	 * @return array list of storage configs
	 */
	protected function readLegacyConfig() {
		// read user config
		$user = $this->getUser()->getUID();
		return \OC_Mount_Config::readData($user);
	}

	/**
	 * Write legacy config data
	 *
	 * @param array $mountPoints
	 */
	protected function writeLegacyConfig(array $mountPoints) {
		// write user config
		$user = $this->getUser()->getUID();
		\OC_Mount_Config::writeData($user, $mountPoints);
	}

	/**
	 * Read the external storages config
	 *
	 * @return array map of storage id to storage config
	 */
	protected function readConfig() {
		$user = $this->getUser()->getUID();
		// TODO: in the future don't rely on the global config reading code
		$storages = parent::readConfig();

		$filteredStorages = [];
		foreach ($storages as $configId => $storage) {
			// filter out all bogus storages that aren't for the current user
			if (!in_array($user, $storage->getApplicableUsers())) {
				continue;
			}

			// clear applicable users, should not be used
			$storage->setApplicableUsers([]);

			// strip out unneeded applicableUser fields
			$filteredStorages[$configId] = $storage;
		}

		return $filteredStorages;
	}

	/**
	 * Write the storages to the user's configuration.
	 *
	 * @param array $storages map of storage id to storage config
	 */
	public function writeConfig($storages) {
		$user = $this->getUser()->getUID();

		// let the horror begin
		$mountPoints = [];
		foreach ($storages as $storageConfig) {
			$mountPoint = $storageConfig->getMountPoint();
			$oldBackendOptions = $storageConfig->getBackendOptions();
			$storageConfig->setBackendOptions(
				\OC_Mount_Config::encryptPasswords(
					$oldBackendOptions
				)
			);

			$rootMountPoint = '/' . $user . '/files/' . ltrim($mountPoint, '/');

			$this->addMountPoint(
				$mountPoints,
				\OC_Mount_Config::MOUNT_TYPE_USER,
				$user,
				$rootMountPoint,
				$storageConfig
			);

			// restore old backend options where the password was not encrypted,
			// because we don't want to change the state of the original object
			$storageConfig->setBackendOptions($oldBackendOptions);
		}

		$this->writeLegacyConfig($mountPoints);
	}

	/**
	 * Triggers $signal for all applicable users of the given
	 * storage
	 *
	 * @param StorageConfig $storage storage data
	 * @param string $signal signal to trigger
	 */
	protected function triggerHooks(StorageConfig $storage, $signal) {
		$user = $this->getUser()->getUID();

		// trigger hook for the current user
		$this->triggerApplicableHooks(
			$signal,
			$storage->getMountPoint(),
			\OC_Mount_Config::MOUNT_TYPE_USER,
			[$user]
		);
	}

	/**
	 * Triggers signal_create_mount or signal_delete_mount to
	 * accomodate for additions/deletions in applicableUsers
	 * and applicableGroups fields.
	 *
	 * @param StorageConfig $oldStorage old storage data
	 * @param StorageConfig $newStorage new storage data
	 */
	protected function triggerChangeHooks(StorageConfig $oldStorage, StorageConfig $newStorage) {
		// if mount point changed, it's like a deletion + creation
		if ($oldStorage->getMountPoint() !== $newStorage->getMountPoint()) {
			$this->triggerHooks($oldStorage, Filesystem::signal_delete_mount);
			$this->triggerHooks($newStorage, Filesystem::signal_create_mount);
		}
	}
}
