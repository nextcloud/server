<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV\Activity\Provider;

use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Card extends Base {
	public const SUBJECT_ADD = 'card_add';
	public const SUBJECT_UPDATE = 'card_update';
	public const SUBJECT_DELETE = 'card_delete';

	public function __construct(
		protected IFactory $languageFactory,
		IURLGenerator $url,
		protected IManager $activityManager,
		IUserManager $userManager,
		IGroupManager $groupManager,
		protected IEventMerger $eventMerger,
		protected IAppManager $appManager,
	) {
		parent::__construct($userManager, $groupManager, $url);
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws UnknownActivityException
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null): IEvent {
		if ($event->getApp() !== 'dav' || $event->getType() !== 'contacts') {
			throw new UnknownActivityException();
		}

		$l = $this->languageFactory->get('dav', $language);

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'places/contacts-dark.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'places/contacts.svg')));
		}

		if ($event->getSubject() === self::SUBJECT_ADD) {
			$subject = $l->t('{actor} created contact {card} in address book {addressbook}');
		} elseif ($event->getSubject() === self::SUBJECT_ADD . '_self') {
			$subject = $l->t('You created contact {card} in address book {addressbook}');
		} elseif ($event->getSubject() === self::SUBJECT_DELETE) {
			$subject = $l->t('{actor} deleted contact {card} from address book {addressbook}');
		} elseif ($event->getSubject() === self::SUBJECT_DELETE . '_self') {
			$subject = $l->t('You deleted contact {card} from address book {addressbook}');
		} elseif ($event->getSubject() === self::SUBJECT_UPDATE) {
			$subject = $l->t('{actor} updated contact {card} in address book {addressbook}');
		} elseif ($event->getSubject() === self::SUBJECT_UPDATE . '_self') {
			$subject = $l->t('You updated contact {card} in address book {addressbook}');
		} else {
			throw new UnknownActivityException();
		}

		$parsedParameters = $this->getParameters($event, $l);
		$this->setSubjects($event, $subject, $parsedParameters);

		$event = $this->eventMerger->mergeEvents('card', $event, $previousEvent);
		return $event;
	}

	protected function getParameters(IEvent $event, IL10N $l): array {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		switch ($subject) {
			case self::SUBJECT_ADD:
			case self::SUBJECT_DELETE:
			case self::SUBJECT_UPDATE:
				return [
					'actor' => $this->generateUserParameter($parameters['actor']),
					'addressbook' => $this->generateAddressbookParameter($parameters['addressbook'], $l),
					'card' => $this->generateCardParameter($parameters['card']),
				];
			case self::SUBJECT_ADD . '_self':
			case self::SUBJECT_DELETE . '_self':
			case self::SUBJECT_UPDATE . '_self':
				return [
					'addressbook' => $this->generateAddressbookParameter($parameters['addressbook'], $l),
					'card' => $this->generateCardParameter($parameters['card']),
				];
		}

		throw new \InvalidArgumentException();
	}

	private function generateCardParameter(array $cardData): array {
		return [
			'type' => 'addressbook-contact',
			'id' => $cardData['id'],
			'name' => $cardData['name'],
		];
	}
}
