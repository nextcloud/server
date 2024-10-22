<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Activity;

use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory as L10nFactory;

class GroupProvider implements IProvider {
	public const ADDED_TO_GROUP = 'group_added';
	public const REMOVED_FROM_GROUP = 'group_removed';

	/** @var string[] */
	protected $groupDisplayNames = [];


	public function __construct(
		private L10nFactory $l10n,
		private IURLGenerator $urlGenerator,
		private IManager $activityManager,
		protected IUserManager $userManager,
		protected IGroupManager $groupManager,
	) {
	}

	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getType() !== 'group_settings') {
			throw new UnknownActivityException();
		}

		$l = $this->l10n->get('settings', $language);

		$params = $event->getSubjectParameters();
		$parsedParameters = [
			'user' => $this->generateUserParameter($params['user']),
			'group' => $this->generateGroupParameter($params['group']),
		];

		if (isset($params['actor'])) {
			$parsedParameters['actor'] = $this->generateUserParameter($params['actor']);
		}

		switch ($event->getSubject()) {
			case self::ADDED_TO_GROUP:
				if (isset($parsedParameters['actor'])) {
					if ($this->activityManager->getCurrentUserId() === $params['user']) {
						$subject = $l->t('{actor} added you to group {group}');
					} elseif (isset($params['actor']) && $this->activityManager->getCurrentUserId() === $params['actor']) {
						$subject = $l->t('You added {user} to group {group}');
					} else {
						$subject = $l->t('{actor} added {user} to group {group}');
					}
				} elseif ($this->activityManager->getCurrentUserId() === $params['user']) {
					$subject = $l->t('An administrator added you to group {group}');
				} else {
					$subject = $l->t('An administrator added {user} to group {group}');
				}
				break;
			case self::REMOVED_FROM_GROUP:
				if (isset($parsedParameters['actor'])) {
					if ($this->activityManager->getCurrentUserId() === $params['user']) {
						$subject = $l->t('{actor} removed you from group {group}');
					} elseif (isset($params['actor']) && $this->activityManager->getCurrentUserId() === $params['actor']) {
						$subject = $l->t('You removed {user} from group {group}');
					} else {
						$subject = $l->t('{actor} removed {user} from group {group}');
					}
				} elseif ($this->activityManager->getCurrentUserId() === $params['user']) {
					$subject = $l->t('An administrator removed you from group {group}');
				} else {
					$subject = $l->t('An administrator removed {user} from group {group}');
				}
				break;
			default:
				throw new UnknownActivityException();
		}

		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	protected function setSubjects(IEvent $event, string $subject, array $parameters): void {
		$event->setRichSubject($subject, $parameters);
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

	protected function generateUserParameter(string $uid): array {
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->userManager->getDisplayName($uid) ?? $uid,
		];
	}
}
