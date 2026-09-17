<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\BackgroundJob;

use OCA\Files\AppInfo\Application;
use OCA\Files\Db\TransferOwnership as Transfer;
use OCA\Files\Db\TransferOwnershipMapper;
use OCA\Files\Exception\TransferOwnershipException;
use OCA\Files\Service\OwnershipTransferService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager as NotificationManager;
use Psr\Log\LoggerInterface;
use function ltrim;

final class TransferOwnership extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private readonly IUserManager $userManager,
		private readonly OwnershipTransferService $transferService,
		private readonly LoggerInterface $logger,
		private readonly NotificationManager $notificationManager,
		private readonly TransferOwnershipMapper $mapper,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct($timeFactory);
	}

	/**
	 * @param array{id: int} $argument
	 */
	#[\Override]
	protected function run($argument): void {
		$id = $argument['id'];

		$transfer = $this->mapper->getById($id);
		$sourceUser = $transfer->sourceUser;
		$destinationUser = $transfer->targetUser;
		$fileId = $transfer->fileId;

		$userFolder = $this->rootFolder->getUserFolder($sourceUser);
		$node = $userFolder->getFirstNodeById($fileId);

		if (!$node) {
			$this->logger->alert('Could not transfer ownership: Node not found');
			$this->failedNotication($transfer);
			return;
		}

		$path = $userFolder->getRelativePath($node->getPath());
		if ($path === null) {
			$this->logger->alert('Could not transfer ownership: Node not found');
			$this->failedNotication($transfer);
			return;
		}

		$sourceUserObject = $this->userManager->get($sourceUser);
		$destinationUserObject = $this->userManager->get($destinationUser);

		if (!$sourceUserObject instanceof IUser) {
			$this->logger->alert('Could not transfer ownership: Unknown source user ' . $sourceUser);
			$this->failedNotication($transfer);
			return;
		}

		if (!$destinationUserObject instanceof IUser) {
			$this->logger->alert("Unknown destination user $destinationUser");
			$this->failedNotication($transfer);
			return;
		}

		try {
			$this->transferService->transfer(
				$sourceUserObject,
				$destinationUserObject,
				ltrim($path, '/')
			);
			$this->successNotification($transfer);
		} catch (TransferOwnershipException $transferOwnershipException) {
			$this->logger->error(
				$transferOwnershipException->getMessage(),
				[
					'exception' => $transferOwnershipException,
				],
			);
			$this->failedNotication($transfer);
		}

		$this->mapper->delete($transfer);
	}

	private function failedNotication(Transfer $transfer): void {
		// Send notification to source user
		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transfer->sourceUser)
			->setApp(Application::APP_ID)
			->setDateTime($this->time->getDateTime())
			->setSubject('transferOwnershipFailedSource', [
				'sourceUser' => $transfer->sourceUser,
				'targetUser' => $transfer->targetUser,
				'nodeName' => $transfer->nodeName,
			])
			->setObject('transfer', (string)$transfer->id);
		$this->notificationManager->notify($notification);
		// Send notification to source user
		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transfer->targetUser)
			->setApp(Application::APP_ID)
			->setDateTime($this->time->getDateTime())
			->setSubject('transferOwnershipFailedTarget', [
				'sourceUser' => $transfer->sourceUser,
				'targetUser' => $transfer->targetUser,
				'nodeName' => $transfer->nodeName,
			])
			->setObject('transfer', (string)$transfer->id);
		$this->notificationManager->notify($notification);
	}

	private function successNotification(Transfer $transfer): void {
		// Send notification to source user
		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transfer->sourceUser)
			->setApp(Application::APP_ID)
			->setDateTime($this->time->getDateTime())
			->setSubject('transferOwnershipDoneSource', [
				'sourceUser' => $transfer->sourceUser,
				'targetUser' => $transfer->targetUser,
				'nodeName' => $transfer->nodeName,
			])
			->setObject('transfer', (string)$transfer->id);
		$this->notificationManager->notify($notification);

		// Send notification to source user
		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transfer->targetUser)
			->setApp(Application::APP_ID)
			->setDateTime($this->time->getDateTime())
			->setSubject('transferOwnershipDoneTarget', [
				'sourceUser' => $transfer->sourceUser,
				'targetUser' => $transfer->targetUser,
				'nodeName' => $transfer->nodeName,
			])
			->setObject('transfer', (string)$transfer->id);
		$this->notificationManager->notify($notification);
	}
}
