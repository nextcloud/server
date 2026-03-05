<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Activity\Providers;

use OCP\Activity\IEvent;

class PublicLinks extends Base {
	public const SUBJECT_SHARED_LINK_SELF = 'shared_link_self';
	public const SUBJECT_RESHARED_LINK_BY = 'reshared_link_by';
	public const SUBJECT_UNSHARED_LINK_SELF = 'unshared_link_self';
	public const SUBJECT_UNSHARED_LINK_BY = 'unshared_link_by';
	public const SUBJECT_LINK_EXPIRED = 'link_expired';
	public const SUBJECT_LINK_BY_EXPIRED = 'link_by_expired';

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parseShortVersion(IEvent $event) {
		$parsedParameters = $this->getParsedParameters($event);

		if ($event->getSubject() === self::SUBJECT_SHARED_LINK_SELF) {
			$subject = $this->l->t('Shared as public link');
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_LINK_SELF) {
			$subject = $this->l->t('Removed public link');
		} elseif ($event->getSubject() === self::SUBJECT_LINK_EXPIRED) {
			$subject = $this->l->t('Public link expired');
		} elseif ($event->getSubject() === self::SUBJECT_RESHARED_LINK_BY) {
			$subject = $this->l->t('{actor} shared as public link');
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_LINK_BY) {
			$subject = $this->l->t('{actor} removed public link');
		} elseif ($event->getSubject() === self::SUBJECT_LINK_BY_EXPIRED) {
			$subject = $this->l->t('Public link of {actor} expired');
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

		if ($event->getSubject() === self::SUBJECT_SHARED_LINK_SELF) {
			$subject = $this->l->t('You shared {file} as public link');
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_LINK_SELF) {
			$subject = $this->l->t('You removed public link for {file}');
		} elseif ($event->getSubject() === self::SUBJECT_LINK_EXPIRED) {
			$subject = $this->l->t('Public link expired for {file}');
		} elseif ($event->getSubject() === self::SUBJECT_RESHARED_LINK_BY) {
			$subject = $this->l->t('{actor} shared {file} as public link');
		} elseif ($event->getSubject() === self::SUBJECT_UNSHARED_LINK_BY) {
			$subject = $this->l->t('{actor} removed public link for {file}');
		} elseif ($event->getSubject() === self::SUBJECT_LINK_BY_EXPIRED) {
			$subject = $this->l->t('Public link of {actor} for {file} expired');
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
			case self::SUBJECT_SHARED_LINK_SELF:
			case self::SUBJECT_UNSHARED_LINK_SELF:
			case self::SUBJECT_LINK_EXPIRED:
				return [
					'file' => $this->getFile($parameters[0], $event),
				];
			case self::SUBJECT_RESHARED_LINK_BY:
			case self::SUBJECT_UNSHARED_LINK_BY:
			case self::SUBJECT_LINK_BY_EXPIRED:
				return [
					'file' => $this->getFile($parameters[0], $event),
					'actor' => $this->getUser($parameters[1]),
				];
		}
		return [];
	}
}
