<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CalDAV\Activity\Provider;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;

abstract class Base implements IProvider {
	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var string[] */
	protected $groupDisplayNames = [];

	/** @var IURLGenerator */
	protected $url;

	/**
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IUserManager $userManager, IGroupManager $groupManager, IURLGenerator $urlGenerator) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->url = $urlGenerator;
	}

	protected function setSubjects(IEvent $event, string $subject, array $parameters): void {
		$event->setRichSubject($subject, $parameters);
	}

	/**
	 * @param array $data
	 * @param IL10N $l
	 * @return array
	 */
	protected function generateCalendarParameter($data, IL10N $l) {
		if ($data['uri'] === CalDavBackend::PERSONAL_CALENDAR_URI &&
			$data['name'] === CalDavBackend::PERSONAL_CALENDAR_NAME) {
			return [
				'type' => 'calendar',
				'id' => $data['id'],
				'name' => $l->t('Personal'),
			];
		}

		return [
			'type' => 'calendar',
			'id' => $data['id'],
			'name' => $data['name'],
		];
	}

	/**
	 * @param int $id
	 * @param string $name
	 * @return array
	 */
	protected function generateLegacyCalendarParameter($id, $name) {
		return [
			'type' => 'calendar',
			'id' => $id,
			'name' => $name,
		];
	}

	protected function generateUserParameter(string $uid): array {
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->userManager->getDisplayName($uid) ?? $uid,
		];
	}

	/**
	 * @param string $gid
	 * @return array
	 */
	protected function generateGroupParameter($gid) {
		if (!isset($this->groupDisplayNames[$gid])) {
			$this->groupDisplayNames[$gid] = $this->getGroupDisplayName($gid);
		}

		return [
			'type' => 'user-group',
			'id' => $gid,
			'name' => $this->groupDisplayNames[$gid],
		];
	}

	/**
	 * @param string $gid
	 * @return string
	 */
	protected function getGroupDisplayName($gid) {
		$group = $this->groupManager->get($gid);
		if ($group instanceof IGroup) {
			return $group->getDisplayName();
		}
		return $gid;
	}
}
