<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	/** @var string[] */
	protected $userDisplayNames = [];

	/** @var string[] */
	protected $groupDisplayNames = [];

	public function __construct(
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
		protected IURLGenerator $url,
	) {
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
		if ($data['uri'] === CardDavBackend::PERSONAL_ADDRESSBOOK_URI
			&& $data['name'] === CardDavBackend::PERSONAL_ADDRESSBOOK_NAME) {
			return [
				'type' => 'addressbook',
				'id' => (string)$data['id'],
				'name' => $l->t('Personal'),
			];
		}

		return [
			'type' => 'addressbook',
			'id' => (string)$data['id'],
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
