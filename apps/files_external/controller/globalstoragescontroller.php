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
use \OCA\Files_external\Service\GlobalStoragesService;
use \OCA\Files_external\NotFoundException;
use \OCA\Files_external\Lib\StorageConfig;

/**
 * Global storages controller
 */
class GlobalStoragesController extends StoragesController {
	/**
	 * Creates a new global storages controller.
	 *
	 * @param string $AppName application name
	 * @param IRequest $request request object
	 * @param IL10N $l10n l10n service
	 * @param GlobalStoragesService $globalStoragesService storage service
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		IL10N $l10n,
		GlobalStoragesService $globalStoragesService
	) {
		parent::__construct(
			$AppName,
			$request,
			$l10n,
			$globalStoragesService
		);
	}

	/**
	 * Create an external storage entry.
	 *
	 * @param string $mountPoint storage mount point
	 * @param string $backendClass backend class name
	 * @param array $backendOptions backend-specific options
	 * @param array $mountOptions mount-specific options
	 * @param array $applicableUsers users for which to mount the storage
	 * @param array $applicableGroups groups for which to mount the storage
	 * @param int $priority priority
	 *
	 * @return DataResponse
	 */
	public function create(
		$mountPoint,
		$backendClass,
		$backendOptions,
		$mountOptions,
		$applicableUsers,
		$applicableGroups,
		$priority
	) {
		$newStorage = new StorageConfig();
		$newStorage->setMountPoint($mountPoint);
		$newStorage->setBackendClass($backendClass);
		$newStorage->setBackendOptions($backendOptions);
		$newStorage->setMountOptions($mountOptions);
		$newStorage->setApplicableUsers($applicableUsers);
		$newStorage->setApplicableGroups($applicableGroups);
		$newStorage->setPriority($priority);

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
	 * @param array $mountOptions mount-specific options
	 * @param array $applicableUsers users for which to mount the storage
	 * @param array $applicableGroups groups for which to mount the storage
	 * @param int $priority priority
	 *
	 * @return DataResponse
	 */
	public function update(
		$id,
		$mountPoint,
		$backendClass,
		$backendOptions,
		$mountOptions,
		$applicableUsers,
		$applicableGroups,
		$priority
	) {
		$storage = new StorageConfig($id);
		$storage->setMountPoint($mountPoint);
		$storage->setBackendClass($backendClass);
		$storage->setBackendOptions($backendOptions);
		$storage->setMountOptions($mountOptions);
		$storage->setApplicableUsers($applicableUsers);
		$storage->setApplicableGroups($applicableGroups);
		$storage->setPriority($priority);

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

}
