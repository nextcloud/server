<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Controller;

use OCA\Files\BackgroundJob\TransferOwnership;
use OCA\Files\Db\TransferOwnership as TransferOwnershipEntity;
use OCA\Files\Db\TransferOwnershipMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Notification\IManager as NotificationManager;

class TransferOwnershipController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private string $userId,
		private NotificationManager $notificationManager,
		private ITimeFactory $timeFactory,
		private IJobList $jobList,
		private TransferOwnershipMapper $mapper,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
	) {
		parent::__construct($appName, $request);
	}


	/**
	 * Transfer the ownership to another user
	 *
	 * @param string $recipient Username of the recipient
	 * @param string $path Path of the file
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN, list<empty>, array{}>
	 *
	 * 200: Ownership transferred successfully
	 * 400: Transferring ownership is not possible
	 * 403: Transferring ownership is not allowed
	 */
	#[NoAdminRequired]
	public function transfer(string $recipient, string $path): DataResponse {
		$recipientUser = $this->userManager->get($recipient);

		if ($recipientUser === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$userRoot = $this->rootFolder->getUserFolder($this->userId);

		try {
			$node = $userRoot->get($path);
		} catch (\Exception $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		if ($node->getOwner()->getUID() !== $this->userId || !$node->getStorage()->instanceOfStorage(IHomeStorage::class)) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$transferOwnership = new TransferOwnershipEntity();
		$transferOwnership->setSourceUser($this->userId);
		$transferOwnership->setTargetUser($recipient);
		$transferOwnership->setFileId($node->getId());
		$transferOwnership->setNodeName($node->getName());
		$transferOwnership = $this->mapper->insert($transferOwnership);

		$notification = $this->notificationManager->createNotification();
		$notification->setUser($recipient)
			->setApp($this->appName)
			->setDateTime($this->timeFactory->getDateTime())
			->setSubject('transferownershipRequest', [
				'sourceUser' => $this->userId,
				'targetUser' => $recipient,
				'nodeName' => $node->getName(),
			])
			->setObject('transfer', (string)$transferOwnership->getId());

		$this->notificationManager->notify($notification);

		return new DataResponse([]);
	}

	/**
	 * Accept an ownership transfer
	 *
	 * @param int $id ID of the ownership transfer
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Ownership transfer accepted successfully
	 * 403: Accepting ownership transfer is not allowed
	 * 404: Ownership transfer not found
	 */
	#[NoAdminRequired]
	public function accept(int $id): DataResponse {
		try {
			$transferOwnership = $this->mapper->getById($id);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if ($transferOwnership->getTargetUser() !== $this->userId) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->jobList->add(TransferOwnership::class, [
			'id' => $transferOwnership->getId(),
		]);

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('files')
			->setObject('transfer', (string)$id);
		$this->notificationManager->markProcessed($notification);

		return new DataResponse([], Http::STATUS_OK);
	}

	/**
	 * Reject an ownership transfer
	 *
	 * @param int $id ID of the ownership transfer
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 200: Ownership transfer rejected successfully
	 * 403: Rejecting ownership transfer is not allowed
	 * 404: Ownership transfer not found
	 */
	#[NoAdminRequired]
	public function reject(int $id): DataResponse {
		try {
			$transferOwnership = $this->mapper->getById($id);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if ($transferOwnership->getTargetUser() !== $this->userId) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('files')
			->setObject('transfer', (string)$id);
		$this->notificationManager->markProcessed($notification);

		$this->mapper->delete($transferOwnership);

		// A "request denied" notification will be created by Notifier::dismissNotification

		return new DataResponse([], Http::STATUS_OK);
	}
}
