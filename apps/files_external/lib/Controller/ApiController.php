<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Controller;

use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\ResponseDefinitions;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Constants;
use OCP\IRequest;

/**
 * @psalm-import-type Files_ExternalMount from ResponseDefinitions
 */
class ApiController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private UserGlobalStoragesService $userGlobalStoragesService,
		private UserStoragesService $userStoragesService,
	) {
		parent::__construct($appName, $request);
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

		$permissions = Constants::PERMISSION_READ;
		// personal mounts can be deleted
		if (!$isSystemMount) {
			$permissions |= Constants::PERMISSION_DELETE;
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
	 * Get the mount points visible for this user
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_ExternalMount>, array{}>
	 *
	 * 200: User mounts returned
	 */
	#[NoAdminRequired]
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
