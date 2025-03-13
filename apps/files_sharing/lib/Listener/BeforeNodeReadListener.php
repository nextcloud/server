<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\Activity\Providers\Downloads;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\ISharedStorage;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Share\IShare;

/**
 * @template-implements IEventListener<BeforeNodeReadEvent|Event>
 */
class BeforeNodeReadListener implements IEventListener {

	public function __construct(
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
		protected \OCP\Activity\IManager $activityManager,
		private IRequest $request,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeNodeReadEvent)) {
			return;
		}

		$node = $event->getNode();
		if (!($node instanceof File)) {
			return;
		}

		try {
			$storage = $node->getStorage();
		} catch (NotFoundException) {
			return;
		}

		if (!$storage->instanceOfStorage(ISharedStorage::class)) {
			return;
		}

		/** @var ISharedStorage $storage */
		$share = $storage->getShare();

		$this->singleFileDownloaded($share, $node);
	}

	/**
	 * create activity if a single file was downloaded from a link share
	 */
	protected function singleFileDownloaded(IShare $share, File $node): void {
		$fileId = $node->getId();

		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
		$userNode = $userFolder->getFirstNodeById($fileId);
		$ownerFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
		$userPath = $userFolder->getRelativePath($userNode?->getPath() ?? '') ?? '';
		$ownerPath = $ownerFolder->getRelativePath($node->getPath()) ?? '';

		$parameters = [$userPath];

		if ($share->getShareType() === IShare::TYPE_EMAIL) {
			$subject = Downloads::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED;
			$parameters[] = $share->getSharedWith();
		} elseif ($share->getShareType() === IShare::TYPE_LINK) {
			$subject = Downloads::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED;
			$remoteAddress = $this->request->getRemoteAddress();
			$dateTime = new \DateTime();
			$dateTime = $dateTime->format('Y-m-d H');
			$remoteAddressHash = md5($dateTime . '-' . $remoteAddress);
			$parameters[] = $remoteAddressHash;
		} else {
			return;
		}

		$this->publishActivity($subject, $parameters, $share->getSharedBy(), $fileId, $userPath);

		if ($share->getShareOwner() !== $share->getSharedBy()) {
			$parameters[0] = $ownerPath;
			$this->publishActivity($subject, $parameters, $share->getShareOwner(), $fileId, $ownerPath);
		}
	}

	/**
	 * publish activity
	 */
	protected function publishActivity(
		string $subject,
		array $parameters,
		string $affectedUser,
		int $fileId,
		string $filePath,
	): void {
		$event = $this->activityManager->generateEvent();
		$event->setApp('files_sharing')
			->setType('public_links')
			->setSubject($subject, $parameters)
			->setAffectedUser($affectedUser)
			->setObject('files', $fileId, $filePath);
		$this->activityManager->publish($event);
	}
}
