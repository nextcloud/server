<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Controller;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\IUserProvided;
use OCA\Files_External\Lib\Auth\Password\UserGlobalAuth;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * User global storages controller
 */
class UserGlobalStoragesController extends StoragesController {
	/**
	 * Creates a new user global storages controller.
	 *
	 * @param string $AppName application name
	 * @param IRequest $request request object
	 * @param IL10N $l10n l10n service
	 * @param UserGlobalStoragesService $userGlobalStoragesService storage service
	 * @param LoggerInterface $logger
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		IL10N $l10n,
		UserGlobalStoragesService $userGlobalStoragesService,
		LoggerInterface $logger,
		IUserSession $userSession,
		IGroupManager $groupManager,
		IConfig $config,
	) {
		parent::__construct(
			$AppName,
			$request,
			$l10n,
			$userGlobalStoragesService,
			$logger,
			$userSession,
			$groupManager,
			$config
		);
	}

	/**
	 * Get all storage entries
	 *
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function index() {
		/** @var UserGlobalStoragesService */
		$service = $this->service;
		$storages = array_map(function ($storage) {
			// remove configuration data, this must be kept private
			$this->sanitizeStorage($storage);
			return $storage->jsonSerialize(true);
		}, $service->getUniqueStorages());

		return new DataResponse(
			$storages,
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

	/**
	 * Get an external storage entry.
	 *
	 * @param int $id storage id
	 * @param bool $testOnly whether to storage should only test the connection or do more things
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function show($id, $testOnly = true) {
		try {
			$storage = $this->service->getStorage($id);

			$this->updateStorageStatus($storage, $testOnly);
		} catch (NotFoundException $e) {
			return new DataResponse(
				[
					'message' => $this->l10n->t('Storage with ID "%d" not found', [$id])
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$this->sanitizeStorage($storage);

		$data = $storage->jsonSerialize(true);
		$isAdmin = $this->groupManager->isAdmin($this->userSession->getUser()->getUID());
		$data['can_edit'] = $storage->getType() === StorageConfig::MOUNT_TYPE_PERSONAL || $isAdmin;

		return new DataResponse(
			$data,
			Http::STATUS_OK
		);
	}

	/**
	 * Update an external storage entry.
	 * Only allows setting user provided backend fields
	 *
	 * @param int $id storage id
	 * @param array $backendOptions backend-specific options
	 * @param bool $testOnly whether to storage should only test the connection or do more things
	 *
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired(strict: true)]
	public function update(
		$id,
		$backendOptions,
		$testOnly = true,
	) {
		try {
			$storage = $this->service->getStorage($id);
			$authMechanism = $storage->getAuthMechanism();
			if ($authMechanism instanceof IUserProvided || $authMechanism instanceof  UserGlobalAuth) {
				$authMechanism->saveBackendOptions($this->userSession->getUser(), $id, $backendOptions);
				$authMechanism->manipulateStorageConfig($storage, $this->userSession->getUser());
			} else {
				return new DataResponse(
					[
						'message' => $this->l10n->t('Storage with ID "%d" is not editable by non-admins', [$id])
					],
					Http::STATUS_FORBIDDEN
				);
			}
		} catch (NotFoundException $e) {
			return new DataResponse(
				[
					'message' => $this->l10n->t('Storage with ID "%d" not found', [$id])
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$this->updateStorageStatus($storage, $testOnly);
		$this->sanitizeStorage($storage);

		return new DataResponse(
			$storage->jsonSerialize(true),
			Http::STATUS_OK
		);
	}

	/**
	 * Remove sensitive data from a StorageConfig before returning it to the user
	 *
	 * @param StorageConfig $storage
	 */
	protected function sanitizeStorage(StorageConfig $storage) {
		$storage->setBackendOptions([]);
		$storage->setMountOptions([]);

		if ($storage->getAuthMechanism() instanceof IUserProvided) {
			try {
				$storage->getAuthMechanism()->manipulateStorageConfig($storage, $this->userSession->getUser());
			} catch (InsufficientDataForMeaningfulAnswerException $e) {
				// not configured yet
			}
		}
	}
}
