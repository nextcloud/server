<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Activity\Provider;

use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;

class Todo extends Event {

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws UnknownActivityException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== 'dav' || $event->getType() !== 'calendar_todo') {
			throw new UnknownActivityException();
		}

		$this->l = $this->languageFactory->get('dav', $language);

		if ($this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/checkmark.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/checkmark.svg')));
		}

		if ($event->getSubject() === self::SUBJECT_OBJECT_ADD . '_todo') {
			$subject = $this->l->t('{actor} created to-do {todo} in list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_ADD . '_todo_self') {
			$subject = $this->l->t('You created to-do {todo} in list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_DELETE . '_todo') {
			$subject = $this->l->t('{actor} deleted to-do {todo} from list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_DELETE . '_todo_self') {
			$subject = $this->l->t('You deleted to-do {todo} from list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_UPDATE . '_todo') {
			$subject = $this->l->t('{actor} updated to-do {todo} in list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_UPDATE . '_todo_self') {
			$subject = $this->l->t('You updated to-do {todo} in list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_UPDATE . '_todo_completed') {
			$subject = $this->l->t('{actor} solved to-do {todo} in list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_UPDATE . '_todo_completed_self') {
			$subject = $this->l->t('You solved to-do {todo} in list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action') {
			$subject = $this->l->t('{actor} reopened to-do {todo} in list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action_self') {
			$subject = $this->l->t('You reopened to-do {todo} in list {calendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_MOVE . '_todo') {
			$subject = $this->l->t('{actor} moved to-do {todo} from list {sourceCalendar} to list {targetCalendar}');
		} elseif ($event->getSubject() === self::SUBJECT_OBJECT_MOVE . '_todo_self') {
			$subject = $this->l->t('You moved to-do {todo} from list {sourceCalendar} to list {targetCalendar}');
		} else {
			throw new UnknownActivityException();
		}

		$parsedParameters = $this->getParameters($event);
		$this->setSubjects($event, $subject, $parsedParameters);

		$event = $this->eventMerger->mergeEvents('todo', $event, $previousEvent);

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
				case self::SUBJECT_OBJECT_ADD . '_todo':
				case self::SUBJECT_OBJECT_DELETE . '_todo':
				case self::SUBJECT_OBJECT_UPDATE . '_todo':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_completed':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action':
					return [
						'actor' => $this->generateUserParameter($parameters['actor']),
						'calendar' => $this->generateCalendarParameter($parameters['calendar'], $this->l),
						'todo' => $this->generateObjectParameter($parameters['object'], $event->getAffectedUser()),
					];
				case self::SUBJECT_OBJECT_ADD . '_todo_self':
				case self::SUBJECT_OBJECT_DELETE . '_todo_self':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_self':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_completed_self':
				case self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action_self':
					return [
						'calendar' => $this->generateCalendarParameter($parameters['calendar'], $this->l),
						'todo' => $this->generateObjectParameter($parameters['object'], $event->getAffectedUser()),
					];
			}
		}

		if (isset($parameters['sourceCalendar']) && isset($parameters['targetCalendar'])) {
			switch ($subject) {
				case self::SUBJECT_OBJECT_MOVE . '_todo':
					return [
						'actor' => $this->generateUserParameter($parameters['actor']),
						'sourceCalendar' => $this->generateCalendarParameter($parameters['sourceCalendar'], $this->l),
						'targetCalendar' => $this->generateCalendarParameter($parameters['targetCalendar'], $this->l),
						'todo' => $this->generateObjectParameter($parameters['object'], $event->getAffectedUser()),
					];
				case self::SUBJECT_OBJECT_MOVE . '_todo_self':
					return [
						'sourceCalendar' => $this->generateCalendarParameter($parameters['sourceCalendar'], $this->l),
						'targetCalendar' => $this->generateCalendarParameter($parameters['targetCalendar'], $this->l),
						'todo' => $this->generateObjectParameter($parameters['object'], $event->getAffectedUser()),
					];
			}
		}

		// Legacy - Do NOT Remove unless necessary
		// Removing this will break parsing of activities that were created on
		// Nextcloud 12, so we should keep this as long as it's acceptable.
		// Otherwise if people upgrade over multiple releases in a short period,
		// they will get the dead entries in their stream.
		switch ($subject) {
			case self::SUBJECT_OBJECT_ADD . '_todo':
			case self::SUBJECT_OBJECT_DELETE . '_todo':
			case self::SUBJECT_OBJECT_UPDATE . '_todo':
			case self::SUBJECT_OBJECT_UPDATE . '_todo_completed':
			case self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action':
				return [
					'actor' => $this->generateUserParameter($parameters[0]),
					'calendar' => $this->generateLegacyCalendarParameter($event->getObjectId(), $parameters[1]),
					'todo' => $this->generateObjectParameter($parameters[2], $event->getAffectedUser()),
				];
			case self::SUBJECT_OBJECT_ADD . '_todo_self':
			case self::SUBJECT_OBJECT_DELETE . '_todo_self':
			case self::SUBJECT_OBJECT_UPDATE . '_todo_self':
			case self::SUBJECT_OBJECT_UPDATE . '_todo_completed_self':
			case self::SUBJECT_OBJECT_UPDATE . '_todo_needs_action_self':
				return [
					'calendar' => $this->generateLegacyCalendarParameter($event->getObjectId(), $parameters[1]),
					'todo' => $this->generateObjectParameter($parameters[2], $event->getAffectedUser()),
				];
		}

		throw new \InvalidArgumentException();
	}
}
