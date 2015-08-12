<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
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

namespace OCA\Files_External\Service;

use \OCA\Files_external\Service\GlobalStoragesService;
use \OCA\Files_External\Service\BackendService;
use \OCP\IUserSession;
use \OCP\IGroupManager;
use \OCA\Files_External\Service\UserTrait;

/**
 * Service class to read global storages applicable to the user
 * Read-only access available, attempting to write will throw DomainException
 */
class UserGlobalStoragesService extends GlobalStoragesService {

	use UserTrait;

	/** @var IGroupManager */
	protected $groupManager;

	/**
	 * @param BackendService $backendService
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		BackendService $backendService,
		IUserSession $userSession,
		IGroupManager $groupManager
	) {
		parent::__construct($backendService);
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
	}

	/**
	 * Replace config hash ID with real IDs, for migrating legacy storages
	 *
	 * @param StorageConfig[] $storages Storages with real IDs
	 * @param StorageConfig[] $storagesWithConfigHash Storages with config hash IDs
	 */
	protected function setRealStorageIds(array &$storages, array $storagesWithConfigHash) {
		// as a read-only view, storage IDs don't need to be real
		foreach ($storagesWithConfigHash as $storage) {
			$storages[$storage->getId()] = $storage;
		}
	}

	/**
	 * Read legacy config data
	 *
	 * @return array list of mount configs
	 */
	protected function readLegacyConfig() {
		// read global config
		$data = parent::readLegacyConfig();
		$userId = $this->getUser()->getUID();

		if (isset($data[\OC_Mount_Config::MOUNT_TYPE_USER])) {
			$data[\OC_Mount_Config::MOUNT_TYPE_USER] = array_filter(
				$data[\OC_Mount_Config::MOUNT_TYPE_USER], function($key) use ($userId) {
					return (strtolower($key) === strtolower($userId) || $key === 'all');
				}, ARRAY_FILTER_USE_KEY
			);
		}

		if (isset($data[\OC_Mount_Config::MOUNT_TYPE_GROUP])) {
			$data[\OC_Mount_Config::MOUNT_TYPE_GROUP] = array_filter(
				$data[\OC_Mount_Config::MOUNT_TYPE_GROUP], function($key) use ($userId) {
					return ($this->groupManager->isInGroup($userId, $key));
				}, ARRAY_FILTER_USE_KEY
			);
		}

		return $data;
	}

	/**
	 * Write legacy config data
	 *
	 * @param array $mountPoints
	 */
	protected function writeLegacyConfig(array $mountPoints) {
		throw new \DomainException('UserGlobalStoragesService writing disallowed');
	}

}
