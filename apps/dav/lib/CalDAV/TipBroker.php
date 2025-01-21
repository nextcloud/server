<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

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
		// Merging attendee lists.
		$attendees = [];
		foreach ($oldEventInfo['attendees'] as $attendee) {
			$attendees[$attendee['href']] = [
				'href' => $attendee['href'],
				'oldInstances' => $attendee['instances'],
				'newInstances' => [],
				'name' => $attendee['name'],
				'forceSend' => null,
			];
		}
		foreach ($eventInfo['attendees'] as $attendee) {
			if (isset($attendees[$attendee['href']])) {
				$attendees[$attendee['href']]['name'] = $attendee['name'];
				$attendees[$attendee['href']]['newInstances'] = $attendee['instances'];
				$attendees[$attendee['href']]['forceSend'] = $attendee['forceSend'];
			} else {
				$attendees[$attendee['href']] = [
					'href' => $attendee['href'],
					'oldInstances' => [],
					'newInstances' => $attendee['instances'],
					'name' => $attendee['name'],
					'forceSend' => $attendee['forceSend'],
				];
			}
		}

		$messages = [];

		foreach ($attendees as $attendee) {
			// An organizer can also be an attendee. We should not generate any
			// messages for those.
			if ($attendee['href'] === $eventInfo['organizer']) {
				continue;
			}

			$message = new Message();
			$message->uid = $eventInfo['uid'];
			$message->component = 'VEVENT';
			$message->sequence = $eventInfo['sequence'];
			$message->sender = $eventInfo['organizer'];
			$message->senderName = $eventInfo['organizerName'];
			$message->recipient = $attendee['href'];
			$message->recipientName = $attendee['name'];

			// Creating the new iCalendar body.
			$icalMsg = new VCalendar();

			foreach ($calendar->select('VTIMEZONE') as $timezone) {
				$icalMsg->add(clone $timezone);
			}
			// If there are no instances the attendee is a part of, it means
			// the attendee was removed and we need to send them a CANCEL message.
			// Also If the meeting STATUS property was changed to CANCELLED
			// we need to send the attendee a CANCEL message.
			if (!$attendee['newInstances'] || $eventInfo['status'] === 'CANCELLED') {
				
				$message->method = $icalMsg->METHOD = 'CANCEL';
				$message->significantChange = true;
				// clone base event
				$event = clone $eventInfo['instances']['master'];
				// alter some properties
				unset($event->ATTENDEE);
				$event->add('ATTENDEE', $attendee['href'], ['CN' => $attendee['name'],]);
				$event->DTSTAMP = gmdate('Ymd\\THis\\Z');
				$event->SEQUENCE = $message->sequence;
				$icalMsg->add($event);
				
			} else {
				// The attendee gets the updated event body
				$message->method = $icalMsg->METHOD = 'REQUEST';

				// We need to find out that this change is significant. If it's
				// not, systems may opt to not send messages.
				//
				// We do this based on the 'significantChangeHash' which is
				// some value that changes if there's a certain set of
				// properties changed in the event, or simply if there's a
				// difference in instances that the attendee is invited to.

				$oldAttendeeInstances = array_keys($attendee['oldInstances']);
				$newAttendeeInstances = array_keys($attendee['newInstances']);

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

					$currentEvent->DTSTAMP = gmdate('Ymd\\THis\\Z');
					$icalMsg->add($currentEvent);
				}
			}

			$message->message = $icalMsg;
			$messages[] = $message;
		}

		return $messages;
	}

}
