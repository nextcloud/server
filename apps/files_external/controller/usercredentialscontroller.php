<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

use OCA\Calendar\Sabre\Backend;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\Password\UserProvided;
use OCA\Files_external\Lib\StorageConfig;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class UserCredentialsController extends StoragesController {
	/**
	 * @var UserProvided
	 */
	private $authMechanism;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var UserGlobalStoragesService
	 */
	private $globalStoragesService;

	public function __construct(
		$appName, IRequest $request,
		UserProvided $authMechanism,
		IUserSession $userSession,
		IL10N $l10n,
		UserGlobalStoragesService $globalStoragesService
	) {
		parent::__construct($appName, $request, $l10n, $globalStoragesService);
		$this->authMechanism = $authMechanism;
		$this->userSession = $userSession;
		$this->globalStoragesService = $globalStoragesService;
	}

	/**
	 * @param int $storageId
	 * @param string $username
	 * @param string $password
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function store($storageId, $username, $password) {
		$this->authMechanism->saveCredentials($this->userSession->getUser(), $storageId, $username, $password);

		$storage = $this->globalStoragesService->getStorage($storageId);

		$this->updateStorageStatus($storage);

		$storage->setBackendOptions([]);
		$storage->setMountOptions([]);
		$this->manipulateStorageConfig($storage);


		return new DataResponse(
			$storage,
			Http::STATUS_OK
		);
	}

	protected function manipulateStorageConfig(StorageConfig $storage) {
		/** @var AuthMechanism */
		$authMechanism = $storage->getAuthMechanism();
		$authMechanism->manipulateStorageConfig($storage, $this->userSession->getUser());
		/** @var Backend */
		$backend = $storage->getBackend();
		$backend->manipulateStorageConfig($storage, $this->userSession->getUser());
	}
}
