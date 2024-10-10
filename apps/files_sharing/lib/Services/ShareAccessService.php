<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Services;

use OCA\Files_Sharing\Activity\Providers\Downloads;
use OCA\Files_Sharing\Event\ShareLinkAccessedEvent;
use OCP\Activity\IManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IRequest;
use OCP\Share\IShare;

/**
 * Service to handle activity and event emission for access to shares.
 */
class ShareAccessService {

	public const SHARE_ACCESS = 'access';
	public const SHARE_AUTH = 'auth';
	public const SHARE_DOWNLOAD = 'download';

	public function __construct(
		protected IEventDispatcher $eventDispatcher,
		protected IManager $activityManager,
		protected IRootFolder $rootFolder,
		protected ITimeFactory $timeFactory,
		protected IRequest $request,
	) {
	}

	public function shareNotFound(IShare $share): void {
		$event = new ShareLinkAccessedEvent($share, ShareLinkAccessedEvent::STEP_ACCESS, 404, 'Share not found');
		$this->eventDispatcher->dispatchTyped($event);
	}

	public function accessShare(IShare $share): void {
		$event = new ShareLinkAccessedEvent($share, ShareLinkAccessedEvent::STEP_ACCESS);
		$this->eventDispatcher->dispatchTyped($event);
	}

	public function accessWrongPassword(IShare $share): void {
		$event = new ShareLinkAccessedEvent($share, ShareLinkAccessedEvent::STEP_AUTH, 403, 'Wrong password');
		$this->eventDispatcher->dispatchTyped($event);
	}

	public function shareDownloaded(IShare $share): void {
		$event = new ShareLinkAccessedEvent($share, ShareLinkAccessedEvent::STEP_DOWNLOAD);
		$this->eventDispatcher->dispatchTyped($event);
	}

	public function sharedFileDownloaded(IShare $share, Node $node): void {
		$incognito = \OC_User::isIncognitoMode();
		\OC_User::setIncognitoMode(true);

		$fileId = $node->getId();
		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
		$userNode = $userFolder->getFirstNodeById($fileId);
		$ownerFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
		$userPath = $userFolder->getRelativePath($userNode->getPath());
		$ownerPath = $ownerFolder->getRelativePath($node->getPath());

		@[$subject, $parameters] = $this->getFileDownloadedSubject($share, $node);
		if ($subject !== null) {
			$parameters = array_merge([$userPath], $parameters);
			$this->publishActivity($subject, $parameters, $share->getSharedBy(), $fileId, $userPath);

			if ($share->getShareOwner() !== $share->getSharedBy()) {
				$parameters[0] = $ownerPath;
				$this->publishActivity($subject, $parameters, $share->getShareOwner(), $fileId, $ownerPath);
			}
		}

		\OC_User::setIncognitoMode($incognito);
	}

	/**
	 * Get the subject and parameters for a shared file download.
	 *
	 * @return null|array{0: string, 1: array}
	 */
	private function getFileDownloadedSubject(IShare $share, Node $node): ?array {
		$isFile = $node instanceof \OCP\Files\File;

		$parameters = [];
		switch ($share->getShareType()) {
			case IShare::TYPE_EMAIL:
				$subject = match ($isFile) {
					true => Downloads::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED,
					false => Downloads::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED,
				};
				$parameters = [$share->getSharedWith()];
				// no break
			case IShare::TYPE_LINK:
				$subject = match ($isFile) {
					true => Downloads::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED,
					false => Downloads::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED,
				};
				$dateTime = $this->timeFactory
					->getDateTime()
					->format('Y-m-d H');
				$remoteAddress = $this->request->getRemoteAddress();
				$parameters = [md5($dateTime . '-' . $remoteAddress)];
				// no break
			default:
				// All other types not yet receive download notifications
		}
		if (isset($subject)) {
			return [$subject, $parameters];
		}
		return null;
	}

	/**
	 * Publish the activity through the activity manager.
	 */
	private function publishActivity(
		string $subject,
		array $parameters,
		string $affectedUser,
		int $fileId,
		string $filePath,
	) {
		$event = $this->activityManager->generateEvent();
		$event->setApp('files_sharing')
			->setType('public_links')
			->setSubject($subject, $parameters)
			->setAffectedUser($affectedUser)
			->setObject('files', $fileId, $filePath);
		$this->activityManager->publish($event);
	}
}
