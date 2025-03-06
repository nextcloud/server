<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Broker;
use Sabre\VObject\ITip\Message;

class TipBroker extends Broker {

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

	/**
	 * This method is used in cases where an event got updated, and we
	 * potentially need to send emails to attendees to let them know of updates
	 * in the events.
	 *
	 * We will detect which attendees got added, which got removed and create
	 * specific messages for these situations.
	 *
	 * @return array
	 */
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
		$objectSequence = $eventInfo['sequence'];
		$organizerHref = $eventInfo['organizer'] ?? $oldEventInfo['organizer'];
		if ($eventInfo['organizerName'] instanceof \Sabre\VObject\Parameter) {
			$organizerName = $eventInfo['organizerName']->getValue();
		} else {
			$organizerName = $eventInfo['organizerName'];
		}
		// detect if the singleton or recurring base instance was converted to non-scheduling
		if (count($eventInfo['instances']) === 0 && count($oldEventInfo['instances'])> 0) {
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
		if (isset($oldEventInfo['instances'])) {
			$instancesDelta = array_diff_key($eventInfo['instances'], $oldEventInfo['instances']);
			foreach ($instancesDelta as $id => $instance) {
				if ($instance->STATUS?->getValue() === 'CANCELLED') {
					foreach ($eventInfo['attendees'] as $attendee) {
						$messages[] = $this->generateMessage(
							[$instance], $organizerHref, $organizerName, $attendee, $objectId, $objectType, $objectSequence, 'CANCEL', $template
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
			$messages[] = $this->generateMessage(
				$instances, $organizerHref, $organizerName, $eventInfo['attendees'][$attendee], $objectId, $objectType, $objectSequence, 'REQUEST', $template
			);
		}

		return $messages;
	}

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
