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
	 * @return array<int,Message>
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
			// If there are no instances the attendee is a part of, it means
			// the attendee was removed and we need to send them a CANCEL message.
			// Also If the meeting STATUS property was changed to CANCELLED
			// we need to send the attendee a CANCEL message.
			if (!$attendee['newInstances'] || $eventInfo['status'] === 'CANCELLED') {
				
				$message->method = $icalMsg->METHOD = 'CANCEL';
				$message->significantChange = true;
				// clone base event
				if (isset($eventInfo['instances']['master'])) {
					$event = clone $eventInfo['instances']['master'];
				} else {
					$event = clone $oldEventInfo['instances']['master'];
				}
				// alter some properties
				unset($event->ATTENDEE);
				$event->add('ATTENDEE', $attendee['href'], ['CN' => $attendee['name'],]);
				$event->DTSTAMP = gmdate('Ymd\\THis\\Z');
				$event->SEQUENCE = $message->sequence;
				$icalMsg->add($event);
				
			} else {
				// The attendee gets the updated event body
				$message->method = $icalMsg->METHOD = 'REQUEST';

			// Skip if no instances left to send
			if (empty($instances)) {
				continue;
			}

			// Add EXDATE for instances the attendee is NOT part of (only for recurring events with master)
			if (isset($instances['master']) && count($eventInfo['instances']) > 1) {
				$masterInstance = clone $instances['master'];
				$excludedDates = [];

				$message->significantChange =
					$attendee['forceSend'] === 'REQUEST' ||
					count($oldAttendeeInstances) !== count($newAttendeeInstances) ||
					count(array_diff($oldAttendeeInstances, $newAttendeeInstances)) > 0 ||
					$oldEventInfo['significantChangeHash'] !== $eventInfo['significantChangeHash'];

				foreach ($attendee['newInstances'] as $instanceId => $instanceInfo) {
					$currentEvent = clone $eventInfo['instances'][$instanceId];
					if ($instanceId === 'master') {
						// We need to find a list of events that the attendee
						// is not a part of to add to the list of exceptions.
						$exceptions = [];
						foreach ($eventInfo['instances'] as $instanceId => $vevent) {
							if (!isset($attendee['newInstances'][$instanceId])) {
								$exceptions[] = $instanceId;
							}
						}

						// If there were exceptions, we need to add it to an
						// existing EXDATE property, if it exists.
						if ($exceptions) {
							if (isset($currentEvent->EXDATE)) {
								$currentEvent->EXDATE->setParts(array_merge(
									$currentEvent->EXDATE->getParts(),
									$exceptions
								));
							} else {
								$currentEvent->EXDATE = $exceptions;
							}
						}

						// Cleaning up any scheduling information that
						// shouldn't be sent along.
						unset($currentEvent->ORGANIZER['SCHEDULE-FORCE-SEND']);
						unset($currentEvent->ORGANIZER['SCHEDULE-STATUS']);

						foreach ($currentEvent->ATTENDEE as $attendee) {
							unset($attendee['SCHEDULE-FORCE-SEND']);
							unset($attendee['SCHEDULE-STATUS']);

							// We're adding PARTSTAT=NEEDS-ACTION to ensure that
							// iOS shows an "Inbox Item"
							if (!isset($attendee['PARTSTAT'])) {
								$attendee['PARTSTAT'] = 'NEEDS-ACTION';
							}
						}
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
