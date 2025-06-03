<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Activity\Provider;

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

class Event extends Base {
	public const SUBJECT_OBJECT_ADD = 'object_add';
	public const SUBJECT_OBJECT_UPDATE = 'object_update';
	public const SUBJECT_OBJECT_MOVE = 'object_move';
	public const SUBJECT_OBJECT_MOVE_TO_TRASH = 'object_move_to_trash';
	public const SUBJECT_OBJECT_RESTORE = 'object_restore';
	public const SUBJECT_OBJECT_DELETE = 'object_delete';

	/** @var IL10N */
	protected $l;

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IEventMerger $eventMerger
	 * @param IAppManager $appManager
	 */
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
	 * @param array $eventData
	 * @return array
	 */
	protected function generateObjectParameter(array $eventData, string $affectedUser): array {
		if (!isset($eventData['id']) || !isset($eventData['name'])) {
			throw new \InvalidArgumentException();
		}

		$params = [
			'type' => 'calendar-event',
			'id' => $eventData['id'],
			'name' => trim($eventData['name']) !== '' ? $eventData['name'] : $this->l->t('Untitled event'),
		];

		if (isset($eventData['link']) && is_array($eventData['link']) && $this->appManager->isEnabledForUser('calendar')) {
			try {
				// The calendar app needs to be manually loaded for the routes to be loaded
				$this->appManager->loadApp('calendar');
				$linkData = $eventData['link'];
				$calendarUri = $this->urlencodeLowerHex($linkData['calendar_uri']);
				if ($affectedUser === $linkData['owner']) {
					$objectId = base64_encode($this->url->getWebroot() . '/remote.php/dav/calendars/' . $linkData['owner'] . '/' . $calendarUri . '/' . $linkData['object_uri']);
				} else {
					// Can't use the "real" owner and calendar names here because we create a custom
					// calendar for incoming shares with the name "<calendar>_shared_by_<sharer>".
					// Hack: Fix the link by generating it for the incoming shared calendar instead,
					//       as seen from the affected user.
					$objectId = base64_encode($this->url->getWebroot() . '/remote.php/dav/calendars/' . $affectedUser . '/' . $calendarUri . '_shared_by_' . $linkData['owner'] . '/' . $linkData['object_uri']);
				}
				$params['link'] = $this->url->linkToRouteAbsolute('calendar.view.indexdirect.edit', [
					'objectId' => $objectId,
				]);
			} catch (\Exception $error) {
				// Do nothing
			}
		}
		return $params;
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws UnknownActivityException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== 'dav' || $event->getType() !== 'calendar_event') {
			throw new UnknownActivityException();
		}

		$this->l = $this->languageFactory->get('dav', $language);

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'places/calendar-dark.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'places/calendar.svg')));
		}

		if ($event->getSubject() === self::SUBJECT_OBJECT_ADD . '_event') {
			$subject = $this->l->t('{actor} created event {event} in calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_ADD . '_event_self') {
			$subject = $this->l->t('You created event {event} in calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_DELETE . '_event') {
			$subject = $this->l->t('{actor} deleted event {event} from calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_DELETE . '_event_self') {
			$subject = $this->l->t('You deleted event {event} from calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_UPDATE . '_event') {
			$subject = $this->l->t('{actor} updated event {event} in calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_UPDATE . '_event_self') {
			$subject = $this->l->t('You updated event {event} in calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_MOVE . '_event') {
			$subject = $this->l->t('{actor} moved event {event} from calendar {sourceCalendar} to calendar {targetCalendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_MOVE . '_event_self') {
			$subject = $this->l->t('You moved event {event} from calendar {sourceCalendar} to calendar {targetCalendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_MOVE_TO_TRASH . '_event') {
			$subject = $this->l->t('{actor} deleted event {event} from calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_MOVE_TO_TRASH . '_event_self') {
			$subject = $this->l->t('You deleted event {event} from calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_RESTORE . '_event') {
			$subject = $this->l->t('{actor} restored event {event} of calendar {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_RESTORE . '_event_self') {
			$subject = $this->l->t('You restored event {event} of calendar {calendar}');
		} else {
			throw new UnknownActivityException();
		}

		$parsedParameters = $this->getParameters($event);
		$this->setSubjects($event, $subject, $parsedParameters);

		$event = $this->eventMerger->mergeEvents('event', $event, $previousEvent);

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @return array
	 */
	protected function getParameters(IEvent $event) {
		$subject = $event->getSubject();
		$parameters = $event->getSubjectParameters();

		// Nextcloud 13+
		if (isset($parameters['calendar'])) {
			switch ($subject) {
				case self::SUBJECT_OBJECT_ADD . '_event':
				case self::SUBJECT_OBJECT_DELETE . '_event':
				case self::SUBJECT_OBJECT_UPDATE . '_event':
				case self::SUBJECT_OBJECT_MOVE_TO_TRASH . '_event':
				case self::SUBJECT_OBJECT_RESTORE . '_event':
					return [
						'actor' => $this->generateUserParameter($parameters['actor']),
						'calendar' => $this->generateCalendarParameter($parameters['calendar'], $this->l),
						'event' => $this->generateClassifiedObjectParameter($parameters['object'], $event->getAffectedUser()),
					];
				case self::SUBJECT_OBJECT_ADD . '_event_self':
				case self::SUBJECT_OBJECT_DELETE . '_event_self':
				case self::SUBJECT_OBJECT_UPDATE . '_event_self':
				case self::SUBJECT_OBJECT_MOVE_TO_TRASH . '_event_self':
				case self::SUBJECT_OBJECT_RESTORE . '_event_self':
					return [
						'calendar' => $this->generateCalendarParameter($parameters['calendar'], $this->l),
						'event' => $this->generateClassifiedObjectParameter($parameters['object'], $event->getAffectedUser()),
					];
			}
		}

		if (isset($parameters['sourceCalendar']) && isset($parameters['targetCalendar'])) {
			switch ($subject) {
				case self::SUBJECT_OBJECT_MOVE . '_event':
					return [
						'actor' => $this->generateUserParameter($parameters['actor']),
						'sourceCalendar' => $this->generateCalendarParameter($parameters['sourceCalendar'], $this->l),
						'targetCalendar' => $this->generateCalendarParameter($parameters['targetCalendar'], $this->l),
						'event' => $this->generateClassifiedObjectParameter($parameters['object'], $event->getAffectedUser()),
					];
				case self::SUBJECT_OBJECT_MOVE . '_event_self':
					return [
						'sourceCalendar' => $this->generateCalendarParameter($parameters['sourceCalendar'], $this->l),
						'targetCalendar' => $this->generateCalendarParameter($parameters['targetCalendar'], $this->l),
						'event' => $this->generateClassifiedObjectParameter($parameters['object'], $event->getAffectedUser()),
					];
			}
		}

		// Legacy - Do NOT Remove unless necessary
		// Removing this will break parsing of activities that were created on
		// Nextcloud 12, so we should keep this as long as it's acceptable.
		// Otherwise if people upgrade over multiple releases in a short period,
		// they will get the dead entries in their stream.
		switch ($subject) {
			case self::SUBJECT_OBJECT_ADD . '_event':
			case self::SUBJECT_OBJECT_DELETE . '_event':
			case self::SUBJECT_OBJECT_UPDATE . '_event':
				return [
					'actor' => $this->generateUserParameter($parameters[0]),
					'calendar' => $this->generateLegacyCalendarParameter($event->getObjectId(), $parameters[1]),
					'event' => $this->generateObjectParameter($parameters[2], $event->getAffectedUser()),
				];
			case self::SUBJECT_OBJECT_ADD . '_event_self':
			case self::SUBJECT_OBJECT_DELETE . '_event_self':
			case self::SUBJECT_OBJECT_UPDATE . '_event_self':
				return [
					'calendar' => $this->generateLegacyCalendarParameter($event->getObjectId(), $parameters[1]),
					'event' => $this->generateObjectParameter($parameters[2], $event->getAffectedUser()),
				];
		}

		throw new \InvalidArgumentException();
	}

	private function generateClassifiedObjectParameter(array $eventData, string $affectedUser): array {
		$parameter = $this->generateObjectParameter($eventData, $affectedUser);
		if (!empty($eventData['classified'])) {
			$parameter['name'] = $this->l->t('Busy');
		}
		return $parameter;
	}

	/**
	 * Return urlencoded string but with lower cased hex sequences.
	 * The remaining casing will be untouched.
	 */
	private function urlencodeLowerHex(string $raw): string {
		return preg_replace_callback(
			'/%[0-9A-F]{2}/',
			static fn (array $matches) => strtolower($matches[0]),
			urlencode($raw),
		);
	}
}
