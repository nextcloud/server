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
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ManagerEvent;

class Listener {
	protected $groupManager;
	protected $activityManager;
	protected $session;

	/**
	 * Listener constructor.
	 *
	 * @param IGroupManager $groupManager
	 * @param IManager $activityManager
	 * @param IUserSession $session
	 */
	public function __construct(IGroupManager $groupManager, IManager $activityManager, IUserSession $session) {
		$this->groupManager = $groupManager;
		$this->activityManager = $activityManager;
		$this->session = $session;
	}

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
