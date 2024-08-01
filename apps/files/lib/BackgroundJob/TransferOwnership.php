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

class TransferOwnership extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private IUserManager $userManager,
		private OwnershipTransferService $transferService,
		private LoggerInterface $logger,
		private NotificationManager $notificationManager,
		private TransferOwnershipMapper $mapper,
		private IRootFolder $rootFolder,
	) {
		parent::__construct($timeFactory);
	}

	protected function run($argument) {
		$id = $argument['id'];

		$transfer = $this->mapper->getById($id);
		$sourceUser = $transfer->getSourceUser();
		$destinationUser = $transfer->getTargetUser();
		$fileId = $transfer->getFileId();

		$userFolder = $this->rootFolder->getUserFolder($sourceUser);
		$node = $userFolder->getFirstNodeById($fileId);

		if (!$node) {
			$this->logger->alert('Could not transfer ownership: Node not found');
			$this->failedNotication($transfer);
			return;
		}
		$path = $userFolder->getRelativePath($node->getPath());

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
		} catch (TransferOwnershipException $e) {
			$this->logger->error(
				$e->getMessage(),
				[
					'exception' => $e,
				],
			);
			$this->failedNotication($transfer);
		}

		$this->mapper->delete($transfer);
	}

	private function failedNotication(Transfer $transfer): void {
		// Send notification to source user
		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transfer->getSourceUser())
			->setApp(Application::APP_ID)
			->setDateTime($this->time->getDateTime())
			->setSubject('transferOwnershipFailedSource', [
				'sourceUser' => $transfer->getSourceUser(),
				'targetUser' => $transfer->getTargetUser(),
				'nodeName' => $transfer->getNodeName(),
			])
			->setObject('transfer', (string)$transfer->getId());
		$this->notificationManager->notify($notification);
		// Send notification to source user
		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transfer->getTargetUser())
			->setApp(Application::APP_ID)
			->setDateTime($this->time->getDateTime())
			->setSubject('transferOwnershipFailedTarget', [
				'sourceUser' => $transfer->getSourceUser(),
				'targetUser' => $transfer->getTargetUser(),
				'nodeName' => $transfer->getNodeName(),
			])
			->setObject('transfer', (string)$transfer->getId());
		$this->notificationManager->notify($notification);
	}

	private function successNotification(Transfer $transfer): void {
		// Send notification to source user
		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transfer->getSourceUser())
			->setApp(Application::APP_ID)
			->setDateTime($this->time->getDateTime())
			->setSubject('transferOwnershipDoneSource', [
				'sourceUser' => $transfer->getSourceUser(),
				'targetUser' => $transfer->getTargetUser(),
				'nodeName' => $transfer->getNodeName(),
			])
			->setObject('transfer', (string)$transfer->getId());
		$this->notificationManager->notify($notification);

		// Send notification to source user
		$notification = $this->notificationManager->createNotification();
		$notification->setUser($transfer->getTargetUser())
			->setApp(Application::APP_ID)
			->setDateTime($this->time->getDateTime())
			->setSubject('transferOwnershipDoneTarget', [
				'sourceUser' => $transfer->getSourceUser(),
				'targetUser' => $transfer->getTargetUser(),
				'nodeName' => $transfer->getNodeName(),
			])
			->setObject('transfer', (string)$transfer->getId());
		$this->notificationManager->notify($notification);
	}
}
