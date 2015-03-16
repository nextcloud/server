<?php
/**
 * ownCloud - files_external
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Vincent Petry <pvince81@owncloud.com>
 * @copyright Vincent Petry 2015
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
use \OCA\Files_external\NotFoundException;
use \OCA\Files_external\Lib\StorageConfig;

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
	){
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

		if ($result != null) {
			return $result;
		}

		// Verify that the mount point applies for the current user
		// Prevent non-admin users from mounting local storage and other disabled backends
		$allowedBackends = \OC_Mount_Config::getPersonalBackends();
		if (!isset($allowedBackends[$storage->getBackendClass()])) {
			return new DataResponse(
				array(
					'message' => (string)$this->l10n->t('Invalid storage backend "%s"', array($storage->getBackendClass()))
				),
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		return null;
	}

	/**
	 * @NoAdminRequired
	 * @{inheritdoc}
	 */
	public function show($id) {
		return parent::show($id);
	}

	/**
	 * Create an external storage entry.
	 *
	 * @param string $mountPoint storage mount point
	 * @param string $backendClass backend class name
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
		$backendOptions,
		$mountOptions
	) {
		$newStorage = new StorageConfig();
		$newStorage->setMountPoint($mountPoint);
		$newStorage->setBackendClass($backendClass);
		$newStorage->setBackendOptions($backendOptions);
		$newStorage->setMountOptions($mountOptions);

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
	 * @param array $backendOptions backend-specific options
	 * @param array $mountOptions backend-specific mount options
	 *
	 * @return DataResponse
	 */
	public function update(
		$id,
		$mountPoint,
		$backendClass,
		$backendOptions,
		$mountOptions
	) {
		$storage = new StorageConfig($id);
		$storage->setMountPoint($mountPoint);
		$storage->setBackendClass($backendClass);
		$storage->setBackendOptions($backendOptions);
		$storage->setMountOptions($mountOptions);

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
	 * {@inheritdoc}
	 * @NoAdminRequired
	 */
	public function destroy($id) {
		return parent::destroy($id);
	}
}

