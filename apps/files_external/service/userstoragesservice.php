<?php
/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_external\Service;

use \OCP\IUserSession;
use \OC\Files\Filesystem;

use \OCA\Files_external\Lib\StorageConfig;
use \OCA\Files_external\NotFoundException;

/**
 * Service class to manage user external storages
 * (aka personal storages)
 */
class UserStoragesService extends StoragesService {
	/**
	 * @var IUserSession
	 */
	private $userSession;

	public function __construct(
		IUserSession $userSession
	) {
		$this->userSession = $userSession;
	}

	/**
	 * Read legacy config data
	 *
	 * @return array list of storage configs
	 */
	protected function readLegacyConfig() {
		// read user config
		$user = $this->userSession->getUser()->getUID();
		return \OC_Mount_Config::readData($user);
	}

	/**
	 * Read the external storages config
	 *
	 * @return array map of storage id to storage config
	 */
	protected function readConfig() {
		$user = $this->userSession->getUser()->getUID();
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
		$user = $this->userSession->getUser()->getUID();

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

		\OC_Mount_Config::writeData($user, $mountPoints);
	}

	/**
	 * Triggers $signal for all applicable users of the given
	 * storage
	 *
	 * @param StorageConfig $storage storage data
	 * @param string $signal signal to trigger
	 */
	protected function triggerHooks(StorageConfig $storage, $signal) {
		$user = $this->userSession->getUser()->getUID();

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
