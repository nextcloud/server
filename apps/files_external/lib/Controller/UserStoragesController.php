<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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


use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCP\ILogger;
use \OCP\IUserSession;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\AppFramework\Http;
use OCA\Files_External\Service\UserStoragesService;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Lib\StorageConfig;
use \OCA\Files_External\Lib\Backend\Backend;

/**
 * User storages controller
 */
class UserStoragesController extends StoragesController {
	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * Creates a new user storages controller.
	 *
	 * @param string $AppName application name
	 * @param IRequest $request request object
	 * @param IL10N $l10n l10n service
	 * @param UserStoragesService $userStoragesService storage service
	 * @param IUserSession $userSession
	 * @param ILogger $logger
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		IL10N $l10n,
		UserStoragesService $userStoragesService,
		IUserSession $userSession,
		ILogger $logger
	) {
		parent::__construct(
			$AppName,
			$request,
			$l10n,
			$userStoragesService,
			$logger
		);
		$this->userSession = $userSession;
	}

	protected function manipulateStorageConfig(StorageConfig $storage) {
		/** @var AuthMechanism */
		$authMechanism = $storage->getAuthMechanism();
		$authMechanism->manipulateStorageConfig($storage, $this->userSession->getUser());
		/** @var Backend */
		$backend = $storage->getBackend();
		$backend->manipulateStorageConfig($storage, $this->userSession->getUser());
	}

	/**
	 * Get all storage entries
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function index() {
		return parent::index();
	}

	/**
	 * Return storage
	 *
	 * @NoAdminRequired
	 *
	 * {@inheritdoc}
	 */
	public function show($id, $testOnly = true) {
		return parent::show($id, $testOnly);
	}

	/**
	 * Create an external storage entry.
	 *
	 * @param string $mountPoint storage mount point
	 * @param string $backend backend identifier
	 * @param string $authMechanism authentication mechanism identifier
	 * @param array $backendOptions backend-specific options
	 * @param array $mountOptions backend-specific mount options
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 */
	public function create(
		$mountPoint,
		$backend,
		$authMechanism,
		$backendOptions,
		$mountOptions
	) {
		$newStorage = $this->createStorage(
			$mountPoint,
			$backend,
			$authMechanism,
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
	 * @param string $backend backend identifier
	 * @param string $authMechanism authentication mechanism identifier
	 * @param array $backendOptions backend-specific options
	 * @param array $mountOptions backend-specific mount options
	 * @param bool $testOnly whether to storage should only test the connection or do more things
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 */
	public function update(
		$id,
		$mountPoint,
		$backend,
		$authMechanism,
		$backendOptions,
		$mountOptions,
		$testOnly = true
	) {
		$storage = $this->createStorage(
			$mountPoint,
			$backend,
			$authMechanism,
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

		$this->updateStorageStatus($storage, $testOnly);

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
