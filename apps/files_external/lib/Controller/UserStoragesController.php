<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Controller;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\UserStoragesService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Override;
use Psr\Log\LoggerInterface;

/**
 * User storages controller
 */
class UserStoragesController extends StoragesController {
	/**
	 * Creates a new user storages controller.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		IL10N $l10n,
		UserStoragesService $userStoragesService,
		LoggerInterface $logger,
		IUserSession $userSession,
		IGroupManager $groupManager,
		IConfig $config,
	) {
		parent::__construct(
			$appName,
			$request,
			$l10n,
			$userStoragesService,
			$logger,
			$userSession,
			$groupManager,
			$config
		);
	}

	protected function manipulateStorageConfig(StorageConfig $storage): void {
		/** @var AuthMechanism */
		$authMechanism = $storage->getAuthMechanism();
		$authMechanism->manipulateStorageConfig($storage, $this->userSession->getUser());
		/** @var Backend */
		$backend = $storage->getBackend();
		$backend->manipulateStorageConfig($storage, $this->userSession->getUser());
	}

	/**
	 * Get all storage entries
	 */
	#[Override]
	#[NoAdminRequired]
	public function index(): DataResponse {
		return parent::index();
	}

	/**
	 * Return storage
	 */
	#[Override]
	#[NoAdminRequired]
	public function show(int $id): DataResponse {
		return parent::show($id);
	}

	/**
	 * Create an external storage entry.
	 *
	 * @param string $mountPoint storage mount point
	 * @param string $backend backend identifier
	 * @param string $authMechanism authentication mechanism identifier
	 * @param array $backendOptions backend-specific options
	 * @param array $mountOptions backend-specific mount options
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired(strict: true)]
	public function create(
		string $mountPoint,
		string $backend,
		string $authMechanism,
		array $backendOptions,
		?array $mountOptions,
	): DataResponse {
		$canCreateNewLocalStorage = $this->config->getSystemValue('files_external_allow_create_new_local', true);
		if (!$canCreateNewLocalStorage && $backend === 'local') {
			return new DataResponse(
				[
					'message' => $this->l10n->t('Forbidden to manage local mounts')
				],
				Http::STATUS_FORBIDDEN
			);
		}
		$newStorage = $this->createStorage(
			$mountPoint,
			$backend,
			$authMechanism,
			$backendOptions,
			$mountOptions
		);
		if ($newStorage instanceof DataResponse) {
			return $newStorage;
		}

		$response = $this->validate($newStorage);
		if (!empty($response)) {
			return $response;
		}

		$newStorage = $this->service->addStorage($newStorage);
		$this->updateStorageStatus($newStorage);

		return new DataResponse(
			$newStorage->jsonSerialize(true),
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
	 * @param ?array $mountOptions backend-specific mount options
	 */
	#[NoAdminRequired]
	#[PasswordConfirmationRequired(strict: true)]
	public function update(
		int $id,
		string $mountPoint,
		string $backend,
		string $authMechanism,
		array $backendOptions,
		?array $mountOptions,
	): DataResponse {
		$storage = $this->createStorage(
			$mountPoint,
			$backend,
			$authMechanism,
			$backendOptions,
			$mountOptions
		);
		if ($storage instanceof DataResponse) {
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
					'message' => $this->l10n->t('Storage with ID "%d" not found', [$id])
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$this->updateStorageStatus($storage);

		return new DataResponse(
			$storage->jsonSerialize(true),
			Http::STATUS_OK
		);
	}

	/**
	 * Delete storage
	 */
	#[Override]
	#[NoAdminRequired]
	#[PasswordConfirmationRequired(strict: true)]
	public function destroy(int $id): DataResponse {
		return parent::destroy($id);
	}
}
