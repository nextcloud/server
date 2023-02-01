<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\DAV\CardDAV\Activity\Provider;

use OCA\DAV\CardDAV\CardDavBackend;
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

	/** @var string[]  */
	protected $userDisplayNames = [];

	/** @var IGroupManager */
	protected $groupManager;

	/** @var string[] */
	protected $groupDisplayNames = [];

	/** @var IURLGenerator */
	protected $url;

	public function __construct(IUserManager $userManager,
								IGroupManager $groupManager,
								IURLGenerator $urlGenerator) {
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
	protected function generateAddressbookParameter(array $data, IL10N $l): array {
		if ($data['uri'] === CardDavBackend::PERSONAL_ADDRESSBOOK_URI &&
			$data['name'] === CardDavBackend::PERSONAL_ADDRESSBOOK_NAME) {
			return [
				'type' => 'addressbook',
				'id' => $data['id'],
				'name' => $l->t('Personal'),
			];
		}

		return [
			'type' => 'addressbook',
			'id' => $data['id'],
			'name' => $data['name'],
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
	protected function generateGroupParameter(string $gid): array {
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
	protected function getGroupDisplayName(string $gid): string {
		$group = $this->groupManager->get($gid);
		if ($group instanceof IGroup) {
			return $group->getDisplayName();
		}
		return $gid;
	}
}
