<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
use OCP\Share;

class Listener {
	/** @var IManager */
	protected $activityManager;
	/** @var IUserSession */
	protected $session;
	/** @var \OCP\App\IAppManager */
	protected $appManager;
	/** @var \OCP\Files\Config\IMountProviderCollection */
	protected $mountCollection;
	/** @var \OCP\Files\IRootFolder */
	protected $rootFolder;

	/**
	 * Listener constructor.
	 *
	 * @param IManager $activityManager
	 * @param IUserSession $session
	 * @param IAppManager $appManager
	 * @param IMountProviderCollection $mountCollection
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(IManager $activityManager,
								IUserSession $session,
								IAppManager $appManager,
								IMountProviderCollection $mountCollection,
								IRootFolder $rootFolder) {
		$this->activityManager = $activityManager;
		$this->session = $session;
		$this->appManager = $appManager;
		$this->mountCollection = $mountCollection;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param CommentsEvent $event
	 */
	public function commentEvent(CommentsEvent $event) {
		if ($event->getComment()->getObjectType() !== 'files'
			|| !in_array($event->getEvent(), [CommentsEvent::EVENT_ADD])
			|| !$this->appManager->isInstalled('activity')) {
			// Comment not for file, not adding a comment or no activity-app enabled (save the energy)
			return;
		}

		// Get all mount point owners
		$cache = $this->mountCollection->getMountCache();
		$mounts = $cache->getMountsForFileId($event->getComment()->getObjectId());
		if (empty($mounts)) {
			return;
		}

		$users = [];
		foreach ($mounts as $mount) {
			$owner = $mount->getUser()->getUID();
			$ownerFolder = $this->rootFolder->getUserFolder($owner);
			$nodes = $ownerFolder->getById($event->getComment()->getObjectId());
			if (!empty($nodes)) {
				/** @var Node $node */
				$node = array_shift($nodes);
				$path = $node->getPath();
				if (strpos($path, '/' . $owner . '/files/') === 0) {
					$path = substr($path, strlen('/' . $owner . '/files'));
				}
				// Get all users that have access to the mount point
				$users = array_merge($users, Share::getUsersSharingFile($path, $owner, true, true));
			}
		}

		$actor = $this->session->getUser();
		if ($actor instanceof IUser) {
			$actor = $actor->getUID();
		} else {
			$actor = '';
		}

		$activity = $this->activityManager->generateEvent();
		$activity->setApp(Extension::APP_NAME)
			->setType(Extension::APP_NAME)
			->setAuthor($actor)
			->setObject($event->getComment()->getObjectType(), $event->getComment()->getObjectId())
			->setMessage(Extension::ADD_COMMENT_MESSAGE, [
				$event->getComment()->getId(),
			]);

		foreach ($users as $user => $path) {
			$activity->setAffectedUser($user);

			$activity->setSubject(Extension::ADD_COMMENT_SUBJECT, [
				$actor,
				$path,
			]);
			$this->activityManager->publish($activity);
		}
	}
}
