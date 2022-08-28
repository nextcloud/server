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
use OCA\Files_External\ResponseDefinitions;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-import-type Files_ExternalMount from ResponseDefinitions
 */
class ApiController extends OCSController {

	private UserGlobalStoragesService $userGlobalStoragesService;
	private UserStoragesService $userStoragesService;

	public function __construct(
		string $appName,
		IRequest $request,
		UserGlobalStoragesService $userGlobalStorageService,
		UserStoragesService $userStorageService
	) {
		parent::__construct($appName, $request);
		$this->userGlobalStoragesService = $userGlobalStorageService;
		$this->userStoragesService = $userStorageService;
	}

	/**
	 * Formats the given mount config to a mount entry.
	 *
	 * @param string $mountPoint mount point name, relative to the data dir
	 * @param StorageConfig $mountConfig mount config to format
	 *
	 * @return Files_ExternalMount
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
			'id' => $mountConfig->getId(),
			'type' => 'dir',
			'name' => basename($mountPoint),
			'path' => $path,
			'permissions' => $permissions,
			'scope' => $isSystemMount ? 'system' : 'personal',
			'backend' => $mountConfig->getBackend()->getText(),
			'class' => $mountConfig->getBackend()->getIdentifier(),
			'config' => $mountConfig->jsonSerialize(true),
		];
		return $entry;
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get the mount points visible for this user
	 *
	 * @return DataResponse<Http::STATUS_OK, Files_ExternalMount[], array{}>
	 *
	 * 200: User mounts returned
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

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Ask for credentials using a browser's native basic auth prompt
	 * Then returns it if provided
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	public function askNativeAuth(): DataResponse {
		if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
			$response = new DataResponse([], Http::STATUS_UNAUTHORIZED);
			$response->addHeader('WWW-Authenticate', 'Basic realm="Storage authentification needed"');
			return $response;
		}

		$user = $_SERVER['PHP_AUTH_USER'];
		$password = $_SERVER['PHP_AUTH_PW'];

		// Reset auth
		unset($_SERVER['PHP_AUTH_USER']);
		unset($_SERVER['PHP_AUTH_PW']);

		// Using 401 again to ensure we clear any cached Authorization
		return new DataResponse([
			'user' => $user,
			'password' => $password,
		], Http::STATUS_UNAUTHORIZED);
	}
}
