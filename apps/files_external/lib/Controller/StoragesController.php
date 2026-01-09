<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Controller;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\MountConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\StoragesService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Base class for storages controllers
 */
abstract class StoragesController extends Controller {
	/**
	 * Creates a new storages controller.
	 *
	 * @param string $AppName application name
	 * @param IRequest $request request object
	 * @param IL10N $l10n l10n service
	 * @param StoragesService $storagesService storage service
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		$appName,
		IRequest $request,
		protected IL10N $l10n,
		protected StoragesService $service,
		protected LoggerInterface $logger,
		protected IUserSession $userSession,
		protected IGroupManager $groupManager,
		protected IConfig $config,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Create a storage from its parameters
	 *
	 * @param string $mountPoint storage mount point
	 * @param string $backend backend identifier
	 * @param string $authMechanism authentication mechanism identifier
	 * @param array $backendOptions backend-specific options
	 * @param array|null $mountOptions mount-specific options
	 * @param array|null $applicableUsers users for which to mount the storage
	 * @param array|null $applicableGroups groups for which to mount the storage
	 * @param int|null $priority priority
	 *
	 * @return StorageConfig|DataResponse
	 */
	protected function createStorage(
		string $mountPoint,
		string $backend,
		string $authMechanism,
		array $backendOptions,
		?array $mountOptions = null,
		?array $applicableUsers = null,
		?array $applicableGroups = null,
		?int $priority = null,
	) {
		$canCreateNewLocalStorage = $this->config->getSystemValue('files_external_allow_create_new_local', true);
		if (!$canCreateNewLocalStorage && $backend === 'local') {
			return new DataResponse(
				[
					'message' => $this->l10n->t('Forbidden to manage local mounts')
				],
				Http::STATUS_FORBIDDEN
			);
		}

		try {
			return $this->service->createStorage(
				$mountPoint,
				$backend,
				$authMechanism,
				$backendOptions,
				$mountOptions,
				$applicableUsers,
				$applicableGroups,
				$priority
			);
		} catch (\InvalidArgumentException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return new DataResponse(
				[
					'message' => $this->l10n->t('Invalid backend or authentication mechanism class')
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
	}

	/**
	 * Validate storage config
	 *
	 * @param StorageConfig $storage storage config
	 *1
	 * @return DataResponse|null returns response in case of validation error
	 */
	protected function validate(StorageConfig $storage) {
		$mountPoint = $storage->getMountPoint();
		if ($mountPoint === '') {
			return new DataResponse(
				[
					'message' => $this->l10n->t('Invalid mount point'),
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		if ($storage->getBackendOption('objectstore')) {
			// objectstore must not be sent from client side
			return new DataResponse(
				[
					'message' => $this->l10n->t('Objectstore forbidden'),
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		/** @var Backend */
		$backend = $storage->getBackend();
		/** @var AuthMechanism */
		$authMechanism = $storage->getAuthMechanism();
		if ($backend->checkDependencies()) {
			// invalid backend
			return new DataResponse(
				[
					'message' => $this->l10n->t('Invalid storage backend "%s"', [
						$backend->getIdentifier(),
					]),
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		if (!$backend->isVisibleFor($this->service->getVisibilityType())) {
			// not permitted to use backend
			return new DataResponse(
				[
					'message' => $this->l10n->t('Not permitted to use backend "%s"', [
						$backend->getIdentifier(),
					]),
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
		if (!$authMechanism->isVisibleFor($this->service->getVisibilityType())) {
			// not permitted to use auth mechanism
			return new DataResponse(
				[
					'message' => $this->l10n->t('Not permitted to use authentication mechanism "%s"', [
						$authMechanism->getIdentifier(),
					]),
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		if (!$backend->validateStorage($storage)) {
			// unsatisfied parameters
			return new DataResponse(
				[
					'message' => $this->l10n->t('Unsatisfied backend parameters'),
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
		if (!$authMechanism->validateStorage($storage)) {
			// unsatisfied parameters
			return new DataResponse(
				[
					'message' => $this->l10n->t('Unsatisfied authentication mechanism parameters'),
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		return null;
	}

	protected function manipulateStorageConfig(StorageConfig $storage) {
		/** @var AuthMechanism */
		$authMechanism = $storage->getAuthMechanism();
		$authMechanism->manipulateStorageConfig($storage);
		/** @var Backend */
		$backend = $storage->getBackend();
		$backend->manipulateStorageConfig($storage);
	}

	/**
	 * Check whether the given storage is available / valid.
	 *
	 * Note that this operation can be time consuming depending
	 * on whether the remote storage is available or not.
	 *
	 * @param StorageConfig $storage storage configuration
	 */
	protected function updateStorageStatus(StorageConfig &$storage) {
		try {
			$this->manipulateStorageConfig($storage);

			/** @var Backend */
			$backend = $storage->getBackend();
			// update status (can be time-consuming)
			$storage->setStatus(
				MountConfig::getBackendStatus(
					$backend->getStorageClass(),
					$storage->getBackendOptions(),
				)
			);
		} catch (InsufficientDataForMeaningfulAnswerException $e) {
			$status = $e->getCode() ?: StorageNotAvailableException::STATUS_INDETERMINATE;
			$storage->setStatus(
				(int)$status,
				$this->l10n->t('Insufficient data: %s', [$e->getMessage()])
			);
		} catch (StorageNotAvailableException $e) {
			$storage->setStatus(
				(int)$e->getCode(),
				$this->l10n->t('%s', [$e->getMessage()])
			);
		} catch (\Exception $e) {
			// FIXME: convert storage exceptions to StorageNotAvailableException
			$storage->setStatus(
				StorageNotAvailableException::STATUS_ERROR,
				get_class($e) . ': ' . $e->getMessage()
			);
		}
	}

	/**
	 * Get all storage entries
	 *
	 * @return DataResponse
	 */
	public function index() {
		$storages = array_map(static fn ($storage) => $storage->jsonSerialize(true), $this->service->getStorages());

		return new DataResponse(
			$storages,
			Http::STATUS_OK
		);
	}

	/**
	 * Get an external storage entry.
	 *
	 * @param int $id storage id
	 *
	 * @return DataResponse
	 */
	public function show(int $id) {
		try {
			$storage = $this->service->getStorage($id);

			$this->updateStorageStatus($storage);
		} catch (NotFoundException $e) {
			return new DataResponse(
				[
					'message' => $this->l10n->t('Storage with ID "%d" not found', [$id]),
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$data = $storage->jsonSerialize(true);
		$isAdmin = $this->groupManager->isAdmin($this->userSession->getUser()->getUID());
		$data['can_edit'] = $storage->getType() === StorageConfig::MOUNT_TYPE_PERSONAL || $isAdmin;

		return new DataResponse(
			$data,
			Http::STATUS_OK
		);
	}

	/**
	 * Deletes the storage with the given id.
	 *
	 * @param int $id storage id
	 *
	 * @return DataResponse
	 */
	#[PasswordConfirmationRequired(strict: true)]
	public function destroy(int $id) {
		try {
			$this->service->removeStorage($id);
		} catch (NotFoundException $e) {
			return new DataResponse(
				[
					'message' => $this->l10n->t('Storage with ID "%d" not found', [$id]),
				],
				Http::STATUS_NOT_FOUND
			);
		}

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}
}
