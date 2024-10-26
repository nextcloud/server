<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Activity\Providers;

use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\Contacts\IManager as IContactsManager;
use OCP\Federation\ICloudIdManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Groups extends Base {
	public const SUBJECT_SHARED_GROUP_SELF = 'shared_group_self';
	public const SUBJECT_RESHARED_GROUP_BY = 'reshared_group_by';

	public const SUBJECT_UNSHARED_GROUP_SELF = 'unshared_group_self';
	public const SUBJECT_UNSHARED_GROUP_BY = 'unshared_group_by';

	public const SUBJECT_EXPIRED_GROUP = 'expired_group';

	/** @var string[] */
	protected $groupDisplayNames = [];

	public function __construct(
		IFactory $languageFactory,
		IURLGenerator $url,
		IManager $activityManager,
		IUserManager $userManager,
		ICloudIdManager $cloudIdManager,
		IContactsManager $contactsManager,
		IEventMerger $eventMerger,
		protected IGroupManager $groupManager,
	) {
		parent::__construct($languageFactory, $url, $activityManager, $userManager, $cloudIdManager, $contactsManager, $eventMerger);
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_SHARED_GROUP_SELF) {
			$subject = $this->l->t('Shared with group {group}');
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_GROUP_SELF) {
			$subject = $this->l->t('Removed share for group {group}');
		} elseif ($event->getSubject() === self::SUBJECT_RESHARED_GROUP_BY) {
			$subject = $this->l->t('{actor} shared with group {group}');
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_GROUP_BY) {
			$subject = $this->l->t('{actor} removed share for group {group}');
		} elseif ($event->getSubject() === self::SUBJECT_EXPIRED_GROUP) {
			$subject = $this->l->t('Share for group {group} expired');
		} else {
			throw new \InvalidArgumentException();
		}

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		}
		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseLongVersion(IEvent $event, ?IEvent $previousEvent = null) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_SHARED_GROUP_SELF) {
			$subject = $this->l->t('You shared {file} with group {group}');
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_GROUP_SELF) {
			$subject = $this->l->t('You removed group {group} from {file}');
		} elseif ($event->getSubject() === self::SUBJECT_RESHARED_GROUP_BY) {
			$subject = $this->l->t('{actor} shared {file} with group {group}');
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_GROUP_BY) {
			$subject = $this->l->t('{actor} removed group {group} from {file}');
		} elseif ($event->getSubject() === self::SUBJECT_EXPIRED_GROUP) {
			$subject = $this->l->t('Share for file {file} with group {group} expired');
		} else {
			throw new \InvalidArgumentException();
		}

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')));
		}
		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	protected function getParsedParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_RESHARED_GROUP_BY:
			case self::SUBJECT_UNSHARED_GROUP_BY:
				return [
					'file' => $this->getFile($parameters[0], $event),
					'group' => $this->generateGroupParameter($parameters[2]),
					'actor' => $this->getUser($parameters[1]),
				];
			case self::SUBJECT_SHARED_GROUP_SELF:
			case self::SUBJECT_UNSHARED_GROUP_SELF:
			case self::SUBJECT_EXPIRED_GROUP:
				return [
					'file' => $this->getFile($parameters[0], $event),
					'group' => $this->generateGroupParameter($parameters[1]),
				];
		}
		return [];
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
