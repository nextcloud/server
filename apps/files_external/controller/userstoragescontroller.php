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

namespace OCA\Files_External\Controller;


use \OCP\IConfig;
use \OCP\IUserSession;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http;
use \OCA\Files_external\Service\UserStoragesService;
use \OCA\Files_External\Service\BackendService;
use \OCA\Files_external\NotFoundException;
use \OCA\Files_external\Lib\StorageConfig;
use \OCA\Files_External\Lib\Backend\Backend;

/**
 * User storages controller
 */
class UserStoragesController extends StoragesController {
	/**
	 * Creates a new user storages controller.
	 *
	 * @param string $AppName application name
	 * @param IRequest $request request object
	 * @param IL10N $l10n l10n service
	 * @param UserStoragesService $userStoragesService storage service
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		IL10N $l10n,
		UserStoragesService $userStoragesService
	) {
		parent::__construct(
			$AppName,
			$request,
			$l10n,
			$userStoragesService
		);
	}

	/**
	 * Validate storage config
	 *
	 * @param StorageConfig $storage storage config
	 *
	 * @return DataResponse|null returns response in case of validation error
	 */
	protected function validate(StorageConfig $storage) {
		$result = parent::validate($storage);

		if ($result !== null) {
			return $result;
		}

		// Verify that the mount point applies for the current user
		// Prevent non-admin users from mounting local storage and other disabled backends
		/** @var Backend */
		$backend = $storage->getBackend();
		if (!$backend->isVisibleFor(BackendService::VISIBILITY_PERSONAL)) {
			return new DataResponse(
				array(
					'message' => (string)$this->l10n->t('Admin-only storage backend "%s"', [
						$storage->getBackend()->getClass()
					])
				),
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		return null;
	}

	/**
	 * Return storage
	 *
	 * @NoAdminRequired
	 *
	 * {@inheritdoc}
	 */
	public function show($id) {
		return parent::show($id);
	}

	/**
	 * Create an external storage entry.
	 *
	 * @param string $mountPoint storage mount point
	 * @param string $backendClass backend class name
	 * @param string $authMechanismClass authentication mechanism class
	 * @param array $backendOptions backend-specific options
	 * @param array $mountOptions backend-specific mount options
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 */
	public function create(
		$mountPoint,
		$backendClass,
		$authMechanismClass,
		$backendOptions,
		$mountOptions
	) {
		$newStorage = $this->createStorage(
			$mountPoint,
			$backendClass,
			$authMechanismClass,
			$backendOptions,
			$mountOptions
		);
		if ($newStorage instanceOf DataResponse) {
			return $newStorage;
		}

		$response = $this->validate($newStorage);
		if (!empty($response)) {
			return $response;
		}

		$newStorage = $this->service->addStorage($newStorage);
		$this->updateStorageStatus($newStorage);

		return new DataResponse(
			$newStorage,
			Http::STATUS_CREATED
		);
	}

	/**
	 * Update an external storage entry.
	 *
	 * @param int $id storage id
	 * @param string $mountPoint storage mount point
	 * @param string $backendClass backend class name
	 * @param string $authMechanismClass authentication mechanism class
	 * @param array $backendOptions backend-specific options
	 * @param array $mountOptions backend-specific mount options
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 */
	public function update(
		$id,
		$mountPoint,
		$backendClass,
		$authMechanismClass,
		$backendOptions,
		$mountOptions
	) {
		$storage = $this->createStorage(
			$mountPoint,
			$backendClass,
			$authMechanismClass,
			$backendOptions,
			$mountOptions
		);
		if ($storage instanceOf DataResponse) {
			return $storage;
		}
		$storage->setId($id);

		$response = $this->validate($storage);
		if (!empty($response)) {
			return $response;
		}

		try {
			$storage = $this->service->updateStorage($storage);
		} catch (NotFoundException $e) {
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t('Storage with id "%i" not found', array($id))
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$this->updateStorageStatus($storage);

		return new DataResponse(
			$storage,
			Http::STATUS_OK
		);

	}

	/**
	 * Delete storage
	 *
	 * @NoAdminRequired
	 *
	 * {@inheritdoc}
	 */
	public function destroy($id) {
		return parent::destroy($id);
	}
}
