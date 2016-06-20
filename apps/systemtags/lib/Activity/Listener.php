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

namespace OCA\SystemTags\Activity;

use OCP\Activity\IManager;
use OCP\App\IAppManager;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ManagerEvent;
use OCP\SystemTag\MapperEvent;
use OCP\SystemTag\TagNotFoundException;

class Listener {
	/** @var IGroupManager */
	protected $groupManager;
	/** @var IManager */
	protected $activityManager;
	/** @var IUserSession */
	protected $session;
	/** @var \OCP\SystemTag\ISystemTagManager */
	protected $tagManager;
	/** @var \OCP\App\IAppManager */
	protected $appManager;
	/** @var \OCP\Files\Config\IMountProviderCollection */
	protected $mountCollection;
	/** @var \OCP\Files\IRootFolder */
	protected $rootFolder;

	/**
	 * Listener constructor.
	 *
	 * @param IGroupManager $groupManager
	 * @param IManager $activityManager
	 * @param IUserSession $session
	 * @param ISystemTagManager $tagManager
	 * @param IAppManager $appManager
	 * @param IMountProviderCollection $mountCollection
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(IGroupManager $groupManager,
								IManager $activityManager,
								IUserSession $session,
								ISystemTagManager $tagManager,
								IAppManager $appManager,
								IMountProviderCollection $mountCollection,
								IRootFolder $rootFolder) {
		$this->groupManager = $groupManager;
		$this->activityManager = $activityManager;
		$this->session = $session;
		$this->tagManager = $tagManager;
		$this->appManager = $appManager;
		$this->mountCollection = $mountCollection;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param ManagerEvent $event
	 */
	public function event(ManagerEvent $event) {
		$actor = $this->session->getUser();
		if ($actor instanceof IUser) {
			$actor = $actor->getUID();
		} else {
			$actor = '';
		}

		$activity = $this->activityManager->generateEvent();
		$activity->setApp(Extension::APP_NAME)
			->setType(Extension::APP_NAME)
			->setAuthor($actor);
		if ($event->getEvent() === ManagerEvent::EVENT_CREATE) {
			$activity->setSubject(Extension::CREATE_TAG, [
				$actor,
				$this->prepareTagAsParameter($event->getTag()),
			]);
		} else if ($event->getEvent() === ManagerEvent::EVENT_UPDATE) {
			$activity->setSubject(Extension::UPDATE_TAG, [
				$actor,
				$this->prepareTagAsParameter($event->getTag()),
				$this->prepareTagAsParameter($event->getTagBefore()),
			]);
		} else if ($event->getEvent() === ManagerEvent::EVENT_DELETE) {
			$activity->setSubject(Extension::DELETE_TAG, [
				$actor,
				$this->prepareTagAsParameter($event->getTag()),
			]);
		} else {
			return;
		}

		$group = $this->groupManager->get('admin');
		if ($group instanceof IGroup) {
			foreach ($group->getUsers() as $user) {
				$activity->setAffectedUser($user->getUID());
				$this->activityManager->publish($activity);
			}
		}
	}

	/**
	 * @param MapperEvent $event
	 */
	public function mapperEvent(MapperEvent $event) {
		$tagIds = $event->getTags();
		if ($event->getObjectType() !== 'files' ||empty($tagIds)
			|| !in_array($event->getEvent(), [MapperEvent::EVENT_ASSIGN, MapperEvent::EVENT_UNASSIGN])
			|| !$this->appManager->isInstalled('activity')) {
			// System tags not for files, no tags, not (un-)assigning or no activity-app enabled (save the energy)
			return;
		}

		try {
			$tags = $this->tagManager->getTagsByIds($tagIds);
		} catch (TagNotFoundException $e) {
			// User assigned/unassigned a non-existing tag, ignore...
			return;
		}

		if (empty($tags)) {
			return;
		}

		// Get all mount point owners
		$cache = $this->mountCollection->getMountCache();
		$mounts = $cache->getMountsForFileId($event->getObjectId());
		if (empty($mounts)) {
			return;
		}

		$users = [];
		foreach ($mounts as $mount) {
			$owner = $mount->getUser()->getUID();
			$ownerFolder = $this->rootFolder->getUserFolder($owner);
			$nodes = $ownerFolder->getById($event->getObjectId());
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
			->setObject($event->getObjectType(), $event->getObjectId());

		foreach ($users as $user => $path) {
			$activity->setAffectedUser($user);

			foreach ($tags as $tag) {
				// don't publish activity for non-admins if tag is invisible
				if (!$tag->isUserVisible() && !$this->groupManager->isAdmin($user)) {
					continue;
				}
				if ($event->getEvent() === MapperEvent::EVENT_ASSIGN) {
					$activity->setSubject(Extension::ASSIGN_TAG, [
						$actor,
						$path,
						$this->prepareTagAsParameter($tag),
					]);
				} else if ($event->getEvent() === MapperEvent::EVENT_UNASSIGN) {
					$activity->setSubject(Extension::UNASSIGN_TAG, [
						$actor,
						$path,
						$this->prepareTagAsParameter($tag),
					]);
				}

				$this->activityManager->publish($activity);
			}
		}
	}

	/**
	 * @param ISystemTag $tag
	 * @return string
	 */
	protected function prepareTagAsParameter(ISystemTag $tag) {
		if (!$tag->isUserVisible()) {
			return '{{{' . $tag->getName() . '|||invisible}}}';
		} else if (!$tag->isUserAssignable()) {
			return '{{{' . $tag->getName() . '|||not-assignable}}}';
		} else {
			return '{{{' . $tag->getName() . '|||assignable}}}';
		}
	}
}
