<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Controller;

use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Global storages controller
 */
class GlobalStoragesController extends StoragesController {
	/**
	 * Creates a new global storages controller.
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IL10N $l10n,
		GlobalStoragesService $globalStoragesService,
		LoggerInterface $logger,
		IUserSession $userSession,
		IGroupManager $groupManager,
		IConfig $config,
	) {
		parent::__construct(
			$appName,
			$request,
			$l10n,
			$globalStoragesService,
			$logger,
			$userSession,
			$groupManager,
			$config
		);
	}

	/**
	 * Create an external storage entry.
	 *
	 * @param string $mountPoint storage mount point
	 * @param string $backend backend identifier
	 * @param string $authMechanism authentication mechanism identifier
	 * @param array $backendOptions backend-specific options
	 * @param ?array $mountOptions mount-specific options
	 * @param ?array $applicableUsers users for which to mount the storage
	 * @param ?array $applicableGroups groups for which to mount the storage
	 * @param ?int $priority priority
	 */
	#[PasswordConfirmationRequired(strict: true)]
	public function create(
		string $mountPoint,
		string $backend,
		string $authMechanism,
		array $backendOptions,
		?array $mountOptions,
		?array $applicableUsers,
		?array $applicableGroups,
		?int $priority,
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
			$mountOptions,
			$applicableUsers,
			$applicableGroups,
			$priority
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
	 * @param ?array $mountOptions mount-specific options
	 * @param ?array $applicableUsers users for which to mount the storage
	 * @param ?array $applicableGroups groups for which to mount the storage
	 * @param ?int $priority priority
	 */
	#[PasswordConfirmationRequired(strict: true)]
	public function update(
		int $id,
		string $mountPoint,
		string $backend,
		string $authMechanism,
		array $backendOptions,
		?array $mountOptions,
		?array $applicableUsers,
		?array $applicableGroups,
		?int $priority,
	): DataResponse {
		$storage = $this->createStorage(
			$mountPoint,
			$backend,
			$authMechanism,
			$backendOptions,
			$mountOptions,
			$applicableUsers,
			$applicableGroups,
			$priority
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
}
