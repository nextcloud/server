<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Comments\Activity;

use OCP\Activity\IManager;
use OCP\App\IAppManager;
use OCP\Comments\CommentsEvent;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IShareHelper;

class Listener {
	public function __construct(
		protected IManager $activityManager,
		protected IUserSession $session,
		protected IAppManager $appManager,
		protected IMountProviderCollection $mountCollection,
		protected IRootFolder $rootFolder,
		protected IShareHelper $shareHelper,
	) {
	}

	public function commentEvent(CommentsEvent $event): void {
		if ($event->getComment()->getObjectType() !== 'files'
			|| $event->getEvent() !== CommentsEvent::EVENT_ADD
			|| !$this->appManager->isInstalled('activity')) {
			// Comment not for file, not adding a comment or no activity-app enabled (save the energy)
			return;
		}

		// Get all mount point owners
		$cache = $this->mountCollection->getMountCache();
		$mounts = $cache->getMountsForFileId((int)$event->getComment()->getObjectId());
		if (empty($mounts)) {
			return;
		}

		$users = [];
		foreach ($mounts as $mount) {
			$owner = $mount->getUser()->getUID();
			$ownerFolder = $this->rootFolder->getUserFolder($owner);
			$nodes = $ownerFolder->getById((int)$event->getComment()->getObjectId());
			if (!empty($nodes)) {
				/** @var Node $node */
				$node = array_shift($nodes);
				$al = $this->shareHelper->getPathsForAccessList($node);
				$users += $al['users'];
			}
		}

		$actor = $this->session->getUser();
		if ($actor instanceof IUser) {
			$actor = $actor->getUID();
		} else {
			$actor = '';
		}

		$activity = $this->activityManager->generateEvent();
		$activity->setApp('comments')
			->setType('comments')
			->setAuthor($actor)
			->setObject($event->getComment()->getObjectType(), (int)$event->getComment()->getObjectId())
			->setMessage('add_comment_message', [
				'commentId' => $event->getComment()->getId(),
			]);

		foreach ($users as $user => $path) {
			// numerical user ids end up as integers from array keys, but string
			// is required
			$activity->setAffectedUser((string)$user);

			$activity->setSubject('add_comment_subject', [
				'actor' => $actor,
				'fileId' => (int)$event->getComment()->getObjectId(),
				'filePath' => trim($path, '/'),
			]);
			$this->activityManager->publish($activity);
		}
	}
}
