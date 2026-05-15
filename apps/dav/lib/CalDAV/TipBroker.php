<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\ITip\Broker;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;
use Sabre\VObject\Property\Boolean;
use Sabre\VObject\Property\ICalendar\CalAddress;
use Sabre\VObject\Property\ICalendar\DateTime;
use Sabre\VObject\Recur\EventIterator;

class TipBroker extends Broker {
	public const INVITATION_FORWARDING_PROPERTY = 'X-NC-INVITATION-FORWARDING';

	public $significantChangeProperties = [
		'DTSTART',
		'DTEND',
		'DURATION',
		'DUE',
		'RRULE',
		'RDATE',
		'EXDATE',
		'STATUS',
		'SUMMARY',
		'DESCRIPTION',
		'LOCATION',
	];

	/**
	 * Processes incoming CANCEL messages.
	 *
	 * This is a message from an organizer, and means that either an
	 * attendee got removed from an event, or an event got cancelled
	 * altogether.
	 *
	 * @param VCalendar $existingObject
	 *
	 * @return VCalendar|null
	 */
	#[\Override]
	protected function processMessageCancel(Message $itipMessage, ?VCalendar $existingObject = null) {
		if ($existingObject === null) {
			return null;
		}

		$componentType = $itipMessage->component;
		$instances = [];

		foreach ($itipMessage->message->$componentType as $component) {
			$instanceId = isset($component->{'RECURRENCE-ID'}) ? $component->{'RECURRENCE-ID'}->getValue() : 'base';
			$instances[$instanceId] = $component;
		}
		// any existing instances should be marked as cancelled
		foreach ($existingObject->$componentType as $component) {
			$instanceId = isset($component->{'RECURRENCE-ID'}) ? $component->{'RECURRENCE-ID'}->getValue() : 'base';
			if (isset($instances[$instanceId])) {
				if (isset($component->STATUS)) {
					$component->STATUS->setValue('CANCELLED');
				} else {
					$component->add('STATUS', 'CANCELLED');
				}
				if (isset($component->SEQUENCE)) {
					$component->SEQUENCE->setValue($itipMessage->sequence);
				} else {
					$component->add('SEQUENCE', $itipMessage->sequence);
				}
				unset($instances[$instanceId]);
			}
		}
		// any remaining instances are new and should be added
		foreach ($instances as $instance) {
			$existingObject->add($instance);
		}

		return $existingObject;
	}

	#[\Override]
	protected function processMessageReply(Message $itipMessage, ?VCalendar $existingObject = null) {
		// A reply can only be processed based on an existing object.
		// If the object is not available, the reply is ignored.
		if ($existingObject === null) {
			return null;
		}
		$instances = [];
		$requestStatus = '2.0';

		/** @var list<VEvent> $vevents */
		$vevents = $itipMessage->message->select('VEVENT');

		// Finding all the instances the attendee replied to.
		foreach ($vevents as $vevent) {
			// Use the Unix timestamp returned by getTimestamp as a unique identifier for the recurrence.
			// The Unix timestamp will be the same for an event, even if the reply from the attendee
			// used a different format/timezone to express the event date-time.
			$recurId = $this->getRecurrenceKey($vevent);
			$attendee = $this->getFirstAttendee($vevent);
			if ($attendee === null) {
				continue;
			}
			$partstat = $attendee->offsetGet('PARTSTAT');
			if (!$partstat instanceof Parameter) {
				continue;
			}
			$instances[$recurId] = $partstat->getValue();
			if (isset($vevent->{'REQUEST-STATUS'})) {
				$requestStatus = $vevent->{'REQUEST-STATUS'}->getValue();
				[$requestStatus] = explode(';', $requestStatus);
			}
		}

		// Now we need to loop through the original organizer event, to find
		// all the instances where we have a reply for.
		$masterObject = $this->getMasterEvent($existingObject);
		$masterAllowInvitationForwarding = $masterObject === null || $this->allowInvitationForwarding($masterObject);

		/** @var list<VEvent> $vevents */
		$vevents = $existingObject->select('VEVENT');

		foreach ($vevents as $vevent) {
			// Use the Unix timestamp returned by getTimestamp as a unique identifier for the recurrence.
			$recurId = $this->getRecurrenceKey($vevent);
			if (isset($instances[$recurId])) {
				$allowInvitationForwarding = $this->allowInvitationForwarding($vevent);
				$attendeeFound = false;
				if (isset($vevent->ATTENDEE)) {
					foreach ($vevent->ATTENDEE as $attendee) {
						if ($attendee->getValue() === $itipMessage->sender) {
							$attendeeFound = true;
							$attendee['PARTSTAT'] = $instances[$recurId];
							$attendee['SCHEDULE-STATUS'] = $requestStatus;
							// Un-setting the RSVP status, because we now know
							// that the attendee already replied.
							unset($attendee['RSVP']);
							break;
						}
					}
				}
				if (!$attendeeFound && $allowInvitationForwarding) {
					// Adding a new attendee. The iTip documentation calls this
					// a party crasher.
					$parameters = [
						'PARTSTAT' => $instances[$recurId],
					];
					if ($itipMessage->senderName) {
						$parameters['CN'] = $itipMessage->senderName;
					}
					$vevent->add('ATTENDEE', $itipMessage->sender, $parameters);
				}
				unset($instances[$recurId]);
			}
		}

		if ($masterObject === null) {
			// No master object, we can't add new instances.
			return null;
		}
		// If we got replies to instances that did not exist in the
		// original list, it means that new exceptions must be created.
		foreach ($instances as $recurId => $partstat) {
			$recurrenceIterator = new EventIterator($existingObject, $itipMessage->uid);
			$found = false;
			$iterations = 1000;
			do {
				$newObject = $recurrenceIterator->getEventObject();
				$recurrenceIterator->next();

				// Compare the Unix timestamp returned by getTimestamp with the previously calculated timestamp.
				// If they are the same, then this is a matching recurrence, even though its date-time may have
				// been expressed in a different format/timezone.
				if (isset($newObject->{'RECURRENCE-ID'}) && $newObject->{'RECURRENCE-ID'}->getDateTime()->getTimestamp() === $recurId) {
					$found = true;
				}
				--$iterations;
			} while ($recurrenceIterator->valid() && !$found && $iterations);

			// Invalid recurrence id. Skipping this object.
			if (!$found) {
				continue;
			}

			$newObject->remove('RRULE');
			$newObject->remove('EXDATE');
			$newObject->remove('RDATE');

			$attendeeFound = false;
			if (isset($newObject->ATTENDEE)) {
				foreach ($newObject->ATTENDEE as $attendee) {
					if ($attendee->getValue() === $itipMessage->sender) {
						$attendeeFound = true;
						$attendee['PARTSTAT'] = $partstat;
						$attendee['SCHEDULE-STATUS'] = $requestStatus;
						unset($attendee['RSVP']);
						break;
					}
				}
			}
			if (!$attendeeFound && !$masterAllowInvitationForwarding) {
				continue;
			}
			if (!$attendeeFound) {
				// Adding a new attendee
				$parameters = [
					'PARTSTAT' => $partstat,
				];
				if ($itipMessage->senderName) {
					$parameters['CN'] = $itipMessage->senderName;
				}
				$newObject->add('ATTENDEE', $itipMessage->sender, $parameters);
			}
			$existingObject->add($newObject);
		}

		return $existingObject;
	}

	protected function getMasterEvent(VCalendar $calendar): ?VEvent {
		/** @var list<VEvent> $vevents */
		$vevents = $calendar->select('VEVENT');
		foreach ($vevents as $vevent) {
			if (!isset($vevent->{'RECURRENCE-ID'})) {
				return $vevent;
			}
		}
		return null;
	}

	/**
	 * @return int|'master'
	 */
	protected function getRecurrenceKey(VEvent $vevent): int|string {
		/** @var list<Property> $recurrenceIds */
		$recurrenceIds = $vevent->select('RECURRENCE-ID');
		foreach ($recurrenceIds as $recurrenceId) {
			if ($recurrenceId instanceof DateTime) {
				return $recurrenceId->getDateTime()->getTimestamp();
			}
		}
		return 'master';
	}

	protected function getFirstAttendee(VEvent $vevent): ?CalAddress {
		/** @var list<Property> $attendees */
		$attendees = $vevent->select('ATTENDEE');
		foreach ($attendees as $attendee) {
			if ($attendee instanceof CalAddress) {
				return $attendee;
			}
		}
		return null;
	}

	protected function allowInvitationForwarding(VEvent $vevent): bool {
		$properties = $vevent->select(self::INVITATION_FORWARDING_PROPERTY);
		foreach ($properties as $property) {
			if ($property instanceof Boolean) {
				return $property->getValue() === 'TRUE';
			}
		}
		return true;
	}

	/**
	 * This method is used in cases where an event got updated, and we
	 * potentially need to send emails to attendees to let them know of updates
	 * in the events.
	 *
	 * We will detect which attendees got added, which got removed and create
	 * specific messages for these situations.
	 *
	 * @return array<int,Message>
	 */
	#[\Override]
	protected function parseEventForOrganizer(VCalendar $calendar, array $eventInfo, array $oldEventInfo) {

		$messages = [];

		// construct template calendar from original calendar without components
		$template = new VCalendar();
		foreach ($template->children() as $property) {
			$template->remove($property);
		}
		foreach ($calendar->children() as $property) {
			if (in_array($property->name, ['METHOD', 'VEVENT', 'VTODO', 'VJOURNAL', 'VFREEBUSY'], true) === false) {
				$template->add(clone $property);
			}
		}
		// extract event information
		$objectId = $eventInfo['uid'];
		if ($calendar->getBaseComponent() === null) {
			$objectType = $calendar->getComponents()[0]->name;
		} else {
			$objectType = $calendar->getBaseComponent()->name;
		}
		$objectSequence = $eventInfo['sequence'] ?? 1;
		$organizerHref = $eventInfo['organizer'] ?? $oldEventInfo['organizer'];
		if ($eventInfo['organizerName'] instanceof \Sabre\VObject\Parameter) {
			$organizerName = $eventInfo['organizerName']->getValue();
		} else {
			$organizerName = $eventInfo['organizerName'];
		}
		// detect if the singleton or recurring base instance was converted to non-scheduling
		if (count($eventInfo['instances']) === 0 && count($oldEventInfo['instances']) > 0) {
			foreach ($oldEventInfo['attendees'] as $attendee) {
				$messages[] = $this->generateMessage(
					$oldEventInfo['instances'], $organizerHref, $organizerName, $attendee, $objectId, $objectType, $objectSequence, 'CANCEL', $template
				);
			}
			return $messages;
		}
		// detect if the singleton or recurring base instance was cancelled
		if ($eventInfo['instances']['master']?->STATUS?->getValue() === 'CANCELLED' && $oldEventInfo['instances']['master']?->STATUS?->getValue() !== 'CANCELLED') {
			foreach ($eventInfo['attendees'] as $attendee) {
				$messages[] = $this->generateMessage(
					$eventInfo['instances'], $organizerHref, $organizerName, $attendee, $objectId, $objectType, $objectSequence, 'CANCEL', $template
				);
			}
			return $messages;
		}
		// detect if a new cancelled instance was created
		$cancelledNewInstances = [];
		if (isset($oldEventInfo['instances'])) {
			$instancesDelta = array_diff_key($eventInfo['instances'], $oldEventInfo['instances']);
			foreach ($instancesDelta as $id => $instance) {
				if ($instance->STATUS?->getValue() === 'CANCELLED') {
					$cancelledNewInstances[] = $id;
					foreach ($eventInfo['attendees'] as $attendee) {
						$messages[] = $this->generateMessage(
							[$id => $instance], $organizerHref, $organizerName, $attendee, $objectId, $objectType, $objectSequence, 'CANCEL', $template
						);
					}
				}
			}
		}
		// detect attendee mutations
		$attendees = array_unique(
			array_merge(
				array_keys($eventInfo['attendees']),
				array_keys($oldEventInfo['attendees'])
			)
		);
		foreach ($attendees as $attendee) {
			// Skip organizer
			if ($attendee === $organizerHref) {
				continue;
			}

			// Skip if SCHEDULE-AGENT=CLIENT (respect RFC 6638)
			if ($this->scheduleAgentServerRules
				&& isset($eventInfo['attendees'][$attendee]['scheduleAgent'])
				&& strtoupper($eventInfo['attendees'][$attendee]['scheduleAgent']) === 'CLIENT') {
				continue;
			}

			// detect if attendee was removed and send cancel message
			if (!isset($eventInfo['attendees'][$attendee]) && isset($oldEventInfo['attendees'][$attendee])) {
				//get all instances of the attendee was removed from.
				$instances = array_intersect_key($oldEventInfo['instances'], array_flip(array_keys($oldEventInfo['attendees'][$attendee]['instances'])));
				$messages[] = $this->generateMessage(
					$instances, $organizerHref, $organizerName, $oldEventInfo['attendees'][$attendee], $objectId, $objectType, $objectSequence, 'CANCEL', $template
				);
				continue;
			}
			// otherwise any created or modified instances will be sent as REQUEST
			$instances = array_intersect_key($eventInfo['instances'], array_flip(array_keys($eventInfo['attendees'][$attendee]['instances'])));

			// Remove already-cancelled new instances from REQUEST
			if (!empty($cancelledNewInstances)) {
				$instances = array_diff_key($instances, array_flip($cancelledNewInstances));
			}

			// Skip if no instances left to send
			if (empty($instances)) {
				continue;
			}

			// Add EXDATE for instances the attendee is NOT part of (only for recurring events with master)
			if (isset($instances['master']) && count($eventInfo['instances']) > 1) {
				$masterInstance = clone $instances['master'];
				$excludedDates = [];

				foreach ($eventInfo['instances'] as $instanceId => $instance) {
					if ($instanceId !== 'master' && !isset($eventInfo['attendees'][$attendee]['instances'][$instanceId])) {
						$excludedDates[] = $instance->{'RECURRENCE-ID'}->getValue();
					}
				}

				if (!empty($excludedDates)) {
					if (isset($masterInstance->EXDATE)) {
						$currentExdates = $masterInstance->EXDATE->getParts();
						$masterInstance->EXDATE->setParts(array_merge($currentExdates, $excludedDates));
					} else {
						$masterInstance->EXDATE = $excludedDates;
					}
					$instances['master'] = $masterInstance;
				}
			}

			$messages[] = $this->generateMessage(
				$instances, $organizerHref, $organizerName, $eventInfo['attendees'][$attendee], $objectId, $objectType, $objectSequence, 'REQUEST', $template
			);
		}

		return $messages;
	}

	/**
	 * Generates an iTip message for a specific attendee
	 *
	 * @param array<string, Component> $instances Array of event instances to include, keyed by instance ID:
	 *                                            - 'master' => Component: The master/base event
	 *                                            - '{RECURRENCE-ID}' => Component: Exception instances
	 * @param string $organizerHref The organizer's calendar-user address (e.g., 'mailto:user@example.com')
	 * @param string|null $organizerName The organizer's display name
	 * @param array $attendee The attendee information containing:
	 *                        - 'href' (string): The attendee's calendar-user address
	 *                        - 'name' (string): The attendee's display name
	 *                        - 'scheduleAgent' (string|null): SCHEDULE-AGENT parameter
	 *                        - 'instances' (array): Instances this attendee is part of
	 * @param string $objectId The UID of the event
	 * @param string $objectType The component type ('VEVENT', 'VTODO', etc.)
	 * @param int $objectSequence The sequence number of the event
	 * @param string $method The iTip method ('REQUEST', 'CANCEL', 'REPLY', etc.)
	 * @param VCalendar $template The template calendar object (without event components)
	 * @return Message The generated iTip message ready to be sent
	 */
	protected function generateMessage(
		array $instances,
		string $organizerHref,
		?string $organizerName,
		array $attendee,
		string $objectId,
		string $objectType,
		int $objectSequence,
		string $method,
		VCalendar $template,
	): Message {

		$recipientAddress = $attendee['href'] ?? '';
		$recipientName = $attendee['name'] ?? '';

		$vObject = clone $template;
		if ($vObject->METHOD && $vObject->METHOD->getValue() !== $method) {
			$vObject->METHOD->setValue($method);
		} else {
			$vObject->add('METHOD', $method);
		}
		foreach ($instances as $instance) {
			$vObject->add($this->componentSanitizeScheduling(clone $instance));
		}

		$message = new Message();
		$message->method = $method;
		$message->uid = $objectId;
		$message->component = $objectType;
		$message->sequence = $objectSequence;
		$message->sender = $organizerHref;
		$message->senderName = $organizerName;
		$message->recipient = $recipientAddress;
		$message->recipientName = $recipientName;
		$message->significantChange = true;
		$message->message = $vObject;

		return $message;

	}

	protected function componentSanitizeScheduling(Component $component): Component {
		// Cleaning up any scheduling information that should not be sent or is missing
		unset($component->ORGANIZER['SCHEDULE-FORCE-SEND'], $component->ORGANIZER['SCHEDULE-STATUS']);
		foreach ($component->ATTENDEE as $attendee) {
			unset($attendee['SCHEDULE-FORCE-SEND'], $attendee['SCHEDULE-STATUS']);

			if (!isset($attendee['PARTSTAT'])) {
				$attendee['PARTSTAT'] = 'NEEDS-ACTION';
			}
		}
		// Sequence is a required property, default is 0
		// https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.7.4
		if ($component->SEQUENCE === null) {
			$component->add('SEQUENCE', 0);
		}

		return $component;
	}

}
