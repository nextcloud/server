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

			$significant = $this->isRequestSignificantForAttendee($attendee, $eventInfo, $oldEventInfo);
			$messages[] = $this->generateMessage(
				$instances, $organizerHref, $organizerName, $eventInfo['attendees'][$attendee], $objectId, $objectType, $objectSequence, 'REQUEST', $template, $significant
			);
		}

		return $messages;
	}

	/**
	 * Decide whether a REQUEST is materially relevant for a specific attendee.
	 *
	 * A REQUEST broadcast is meaningful only when something the attendee would
	 * actually care about changed: scheduling-significant properties on the
	 * master or on an instance they participate in, the introduction of a new
	 * override that overrides inherited master state, the attendee being added
	 * or partially removed, or a forced SCHEDULE-FORCE-SEND=REQUEST.
	 *
	 * PARTSTAT changes alone are NOT significant for organizer→attendee
	 * REQUEST broadcasts; that flow is REPLY-only per RFC 5546. Suppressing
	 * here fixes the per-occurrence accept/decline re-invite spam.
	 */
	protected function isRequestSignificantForAttendee(
		string $attendeeHref,
		array $eventInfo,
		array $oldEventInfo,
	): bool {
		$newAttendee = $eventInfo['attendees'][$attendeeHref] ?? null;
		$oldAttendee = $oldEventInfo['attendees'][$attendeeHref] ?? null;

		if (($newAttendee['forceSend'] ?? null) === 'REQUEST') {
			return true;
		}
		if ($oldAttendee === null) {
			return true;
		}

		$newMaster = $eventInfo['instances']['master'] ?? null;
		$oldMaster = $oldEventInfo['instances']['master'] ?? null;
		if (($newMaster === null) !== ($oldMaster === null)) {
			return true;
		}
		if ($newMaster !== null && $oldMaster !== null
			&& $this->instancesDifferInSignificantProperties($newMaster, $oldMaster)) {
			return true;
		}

		foreach (($newAttendee['instances'] ?? []) as $instanceId => $_) {
			if ($instanceId === 'master') {
				continue;
			}
			$newInstance = $eventInfo['instances'][$instanceId] ?? null;
			if ($newInstance === null) {
				continue;
			}
			$oldInstance = $oldEventInfo['instances'][$instanceId] ?? null;
			if ($oldInstance !== null) {
				if ($this->instancesDifferInSignificantProperties($newInstance, $oldInstance)) {
					return true;
				}
			} elseif ($oldMaster !== null
				&& $this->overrideDiffersFromInheritedMaster($newInstance, $oldMaster)) {
				return true;
			}
		}

		foreach (($oldAttendee['instances'] ?? []) as $instanceId => $_) {
			if (!isset($newAttendee['instances'][$instanceId])) {
				return true;
			}
		}

		// Synthesized-EXDATE diff: parseEventForOrganizer injects a per-attendee
		// EXDATE on the outbound master for every override the attendee is not
		// part of. If this synthesized set changed between old and new state
		// (a brand-new override now excludes them, or an override they weren't
		// on was removed), the REQUEST content materially changes and they
		// must receive it.
		$newSynthExdates = $this->synthesizedExdatesForAttendee($eventInfo, $newAttendee);
		$oldSynthExdates = $this->synthesizedExdatesForAttendee($oldEventInfo, $oldAttendee);
		if ($newSynthExdates !== $oldSynthExdates) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $eventInfo An eventInfo dict as produced by parseEventInfo()
	 * @param array $attendee The merged attendee dict for the recipient
	 * @return list<string> Sorted list of RECURRENCE-ID values for overrides
	 *                      this attendee is not on (the EXDATE values that would
	 *                      be injected into their REQUEST master).
	 */
	protected function synthesizedExdatesForAttendee(array $eventInfo, array $attendee): array {
		$exdates = [];
		foreach (($eventInfo['instances'] ?? []) as $instanceId => $instance) {
			if ($instanceId === 'master'
				|| isset($attendee['instances'][$instanceId])
				|| !isset($instance->{'RECURRENCE-ID'})) {
				continue;
			}
			$exdates[] = (string)$instance->{'RECURRENCE-ID'}->getValue();
		}
		sort($exdates);
		return $exdates;
	}

	/**
	 * Decide whether a newly-introduced override actually changes anything the
	 * attendee would have inherited from the master.
	 *
	 * Unlike a generic significant-property diff, this is asymmetric: the
	 * override's DTSTART/DTEND are *expected* to differ from the master's
	 * (they describe a different occurrence). What matters is whether the
	 * override shifts the time relative to its own RECURRENCE-ID, changes
	 * duration, or rewrites any date-independent property.
	 */
	protected function overrideDiffersFromInheritedMaster(Component $override, Component $master): bool {
		foreach (['SUMMARY', 'LOCATION', 'DESCRIPTION', 'STATUS'] as $prop) {
			$oVal = isset($override->$prop) ? (string)$override->$prop->getValue() : null;
			$mVal = isset($master->$prop) ? (string)$master->$prop->getValue() : null;
			if ($oVal !== $mVal) {
				return true;
			}
		}

		if (isset($override->DTSTART, $override->{'RECURRENCE-ID'})) {
			try {
				$dtstart = $override->DTSTART->getDateTime();
				$recurId = $override->{'RECURRENCE-ID'}->getDateTime();
				if ($dtstart->getTimestamp() !== $recurId->getTimestamp()) {
					return true;
				}
			} catch (\Exception $e) {
				return true;
			}
		}

		try {
			$overrideDuration = $this->computeDurationSeconds($override);
			$masterDuration = $this->computeDurationSeconds($master);
			if ($overrideDuration !== null && $masterDuration !== null
				&& $overrideDuration !== $masterDuration) {
				return true;
			}
		} catch (\Exception $e) {
			return true;
		}

		// An override should not carry its own recurrence rules; if it does,
		// the organizer is doing something unusual and we should not suppress.
		foreach (['RRULE', 'RDATE', 'EXDATE'] as $prop) {
			if (isset($override->$prop)) {
				return true;
			}
		}

		return false;
	}

	protected function computeDurationSeconds(Component $vevent): ?int {
		if (isset($vevent->DTSTART, $vevent->DTEND)) {
			return $vevent->DTEND->getDateTime()->getTimestamp()
				- $vevent->DTSTART->getDateTime()->getTimestamp();
		}
		if (isset($vevent->DURATION)) {
			$interval = $vevent->DURATION->getDateInterval();
			$ref = new \DateTimeImmutable('@0');
			return $ref->add($interval)->getTimestamp();
		}
		return null;
	}

	protected function instancesDifferInSignificantProperties(Component $a, Component $b): bool {
		foreach ($this->significantChangeProperties as $prop) {
			$aValues = [];
			foreach ($a->select($prop) as $val) {
				$aValues[] = (string)$val->getValue();
			}
			$bValues = [];
			foreach ($b->select($prop) as $val) {
				$bValues[] = (string)$val->getValue();
			}
			sort($aValues);
			sort($bValues);
			if ($aValues !== $bValues) {
				return true;
			}
		}
		return false;
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
	 * @param bool|null $significantChange Whether the change is significant for this attendee.
	 *                                     Defaults to true to preserve historical behavior for
	 *                                     CANCEL paths. REQUEST callers compute it explicitly.
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
		?bool $significantChange = null,
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
		$message->significantChange = $significantChange ?? true;
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
