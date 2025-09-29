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
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\ISharedStorage;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\ISession;
use OCP\Share\IShare;

/**
 * @template-implements IEventListener<BeforeNodeReadEvent|BeforeZipCreatedEvent|Event>
 */
class BeforeNodeReadListener implements IEventListener {
	private ICache $cache;

	public function __construct(
		private ISession $session,
		private IRootFolder $rootFolder,
		private \OCP\Activity\IManager $activityManager,
		private IRequest $request,
		ICacheFactory $cacheFactory,
	) {
		$this->cache = $cacheFactory->createDistributed('files_sharing_activity_events');
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeZipCreatedEvent) {
			$this->handleBeforeZipCreatedEvent($event);
		} elseif ($event instanceof BeforeNodeReadEvent) {
			$this->handleBeforeNodeReadEvent($event);
		}
	}

	public function handleBeforeZipCreatedEvent(BeforeZipCreatedEvent $event): void {
		$files = $event->getFiles();
		if (count($files) !== 0) {
			/* No need to do anything, activity will be triggered for each file in the zip by the BeforeNodeReadEvent */
			return;
		}

		$node = $event->getFolder();
		if (!($node instanceof Folder)) {
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

		if (!in_array($share->getShareType(), [IShare::TYPE_EMAIL, IShare::TYPE_LINK])) {
			return;
		}

		/* Cache that that folder download activity was published */
		$this->cache->set($this->request->getId(), $node->getPath(), 3600);

		$this->singleFileDownloaded($share, $node);
	}

	public function handleBeforeNodeReadEvent(BeforeNodeReadEvent $event): void {
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

		if (!in_array($share->getShareType(), [IShare::TYPE_EMAIL, IShare::TYPE_LINK])) {
			return;
		}

		$path = $this->cache->get($this->request->getId());
		if (is_string($path) && str_starts_with($node->getPath(), $path)) {
			/* An activity was published for a containing folder already */
			return;
		}

		/* Avoid publishing several activities for one video playing */
		$cacheKey = $node->getId() . $node->getPath() . $this->session->getId();
		if (($this->request->getHeader('range') !== '') && ($this->cache->get($cacheKey) === 'true')) {
			/* This is a range request and an activity for the same file was published in the same session */
			return;
		}
		$this->cache->set($cacheKey, 'true', 3600);

		$this->singleFileDownloaded($share, $node);
	}

	/**
	 * create activity if a single file or folder was downloaded from a link share
	 */
	protected function singleFileDownloaded(IShare $share, File|Folder $node): void {
		$fileId = $node->getId();

		$userFolder = $this->rootFolder->getUserFolder($share->getSharedBy());
		$userNode = $userFolder->getFirstNodeById($fileId);
		$ownerFolder = $this->rootFolder->getUserFolder($share->getShareOwner());
		$userPath = $userFolder->getRelativePath($userNode?->getPath() ?? '') ?? '';
		$ownerPath = $ownerFolder->getRelativePath($node->getPath()) ?? '';

		$parameters = [$userPath];

		if ($share->getShareType() === IShare::TYPE_EMAIL) {
			if ($node instanceof File) {
				$subject = Downloads::SUBJECT_SHARED_FILE_BY_EMAIL_DOWNLOADED;
			} else {
				$subject = Downloads::SUBJECT_SHARED_FOLDER_BY_EMAIL_DOWNLOADED;
			}
			$parameters[] = $share->getSharedWith();
		} elseif ($share->getShareType() === IShare::TYPE_LINK) {
			if ($node instanceof File) {
				$subject = Downloads::SUBJECT_PUBLIC_SHARED_FILE_DOWNLOADED;
			} else {
				$subject = Downloads::SUBJECT_PUBLIC_SHARED_FOLDER_DOWNLOADED;
			}
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
