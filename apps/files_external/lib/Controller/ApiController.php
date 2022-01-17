<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jesús Macias <jmacias@solidgear.es>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External\Controller;

use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

class ApiController extends OCSController {

	/** @var IUserSession */
	private $userSession;
	/** @var UserGlobalStoragesService */
	private $userGlobalStoragesService;
	/** @var UserStoragesService */
	private $userStoragesService;

	public function __construct(
		string $appName,
		IRequest $request,
		IUserSession $userSession,
		UserGlobalStoragesService $userGlobalStorageService,
		UserStoragesService $userStorageService
	) {
		parent::__construct($appName, $request);

		$this->userSession = $userSession;
		$this->userGlobalStoragesService = $userGlobalStorageService;
		$this->userStoragesService = $userStorageService;
	}

	/**
	 * Formats the given mount config to a mount entry.
	 *
	 * @param string $mountPoint mount point name, relative to the data dir
	 * @param StorageConfig $mountConfig mount config to format
	 *
	 * @return array entry
	 */
	private function formatMount(string $mountPoint, StorageConfig $mountConfig): array {
		// split path from mount point
		$path = \dirname($mountPoint);
		if ($path === '.' || $path === '/') {
			$path = '';
		}

		$isSystemMount = $mountConfig->getType() === StorageConfig::MOUNT_TYPE_ADMIN;

		$permissions = \OCP\Constants::PERMISSION_READ;
		// personal mounts can be deleted
		if (!$isSystemMount) {
			$permissions |= \OCP\Constants::PERMISSION_DELETE;
		}

		$entry = [
			'name' => basename($mountPoint),
			'path' => $path,
			'type' => 'dir',
			'backend' => $mountConfig->getBackend()->getText(),
			'scope' => $isSystemMount ? 'system' : 'personal',
			'permissions' => $permissions,
			'id' => $mountConfig->getId(),
			'class' => $mountConfig->getBackend()->getIdentifier(),
		];
		return $entry;
	}

	/**
	 * @NoAdminRequired
	 *
	 * Returns the mount points visible for this user.
	 *
	 * @return DataResponse share information
	 */
	public function getUserMounts(): DataResponse {
		$entries = [];
		$mountPoints = [];

		foreach ($this->userGlobalStoragesService->getStorages() as $storage) {
			$mountPoint = $storage->getMountPoint();
			$mountPoints[$mountPoint] = $storage;
		}

		foreach ($this->userStoragesService->getStorages() as $storage) {
			$mountPoint = $storage->getMountPoint();
			$mountPoints[$mountPoint] = $storage;
		}
		foreach ($mountPoints as $mountPoint => $mount) {
			$entries[] = $this->formatMount($mountPoint, $mount);
		}

		return new DataResponse($entries);
	}
}
