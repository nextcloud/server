<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV;

use DateTimeInterface;
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

	private const SINGLETON_PROPERTIES = [
		'UID',
		'DTSTAMP',
		'CREATED',
		'LAST-MODIFIED',
		'DTSTART',
		'DTEND',
		'DURATION',
		'RRULE',
		'STATUS',
		'CLASS',
		'PRIORITY',
		'TRANSP',
		'ORGANIZER',
		'SUMMARY',
		'DESCRIPTION',
		'GEO',
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
		$originalInstances = $oldEventInfo['instances'] ?? [];
		$mutatedInstances = $eventInfo['instances'] ?? [];
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

		// detect deletions of singleton or recurring (base) event
		// detect conversion of singleton or recurring (base) event from attended to attendeeless
		if (count($eventInfo['attendees']) === 0 && count($oldEventInfo['attendees']) > 0) {
			$component = clone $calendar->getBaseComponent();
			$component->remove('ATTENDEE');
			if ($component->ORGANIZER === null) {
				$component->add(clone $originalInstances['master']->ORGANIZER);
			}
			$attendees = [];
			foreach ($originalInstances as $instance) {
				foreach ($instance->ATTENDEE as $attendee) {
					$value = $attendee->getValue();
					if (!isset($attendees[$value])) {
						$component->add(clone $attendee);
						$attendees[$value] = true;
					}
				}
			}
			return $this->instanceCancelledByOrganizer($component, $template);
		}

		foreach ($mutatedInstances as $mutatedInstanceId => $mutatedInstance) {
			if ($mutatedInstance->ORGANIZER['SCHEDULE-AGENT'] !== null && $mutatedInstance->ORGANIZER['SCHEDULE-AGENT'] !== 'SERVER') {
				continue;
			}
			
			$originalInstance = $originalInstances[$mutatedInstanceId] ?? null;
			$action = 'ALTERED';
			
			if ($mutatedInstance->STATUS?->getValue() === 'CANCELLED') {
				if ($originalInstance?->STATUS?->getValue() !== 'CANCELLED') {
					$action = 'CANCELLED';
				} else {
					continue;
				}
			}

			$messages = array_merge($messages, match ($action) {
				'ALTERED' => $this->instanceCreatedOrModifiedByOrganizer($mutatedInstance, $originalInstance, $template),
				'CANCELLED' => $this->instanceCancelledByOrganizer($mutatedInstance, $template),
				default => [],
			});
		}

		return $messages;
	}

	protected function instanceCreatedOrModifiedByOrganizer(Component $mutated, ?Component $original = null, VCalendar $template): array {
	
		$component = clone $mutated;
		$componentDelta = $this->componentDelta($mutated, $original);
		$componentDeltaSignificant = count(array_intersect(array_keys($componentDelta), $this->significantChangeProperties)) > 0;

		if ($componentDelta === []) {
			return [];
		}

		$attendeesDelta = $this->propertyDeltaAttendee($mutated, $original);
		
		// If the mutated object does not have an organizer, the organizer
		// changed the object from a scheduling object to a non-scheduling object
		if (!isset($component->ORGANIZER) && isset($original->ORGANIZER)) {
			$component->add($original->ORGANIZER);
		}
		$senderAddress = $component->ORGANIZER->getValue();
		$senderName = $component->ORGANIZER['CN']->getValue() ?? null;

		$component = $this->componentSanitizeScheduling($component);
		
		$messages = [];
		foreach ($attendeesDelta as $type => $attendees) {
			$method = match ($type) {
				'Removed' => 'CANCEL',
				default => 'REQUEST',
			};
			foreach ($attendees as $attendee) {
				$recipientAddress = $attendee->getValue();
				$recipientName = $attendee['CN']?->getValue() ?? null;
				
				if ($senderAddress === $recipientAddress) {
					continue;
				}
				if ($attendee['SCHEDULE-AGENT'] !== null && $attendee['SCHEDULE-AGENT'] !== 'SERVER') {
					continue;
				}
				if ($attendee['SCHEDULE-FORCE-SEND'] !== null) {
					$method = $attendee['SCHEDULE-FORCE-SEND'];
				}

				$messages[] = $this->generateMessage(
					$method,
					$senderAddress,
					$senderName,
					$recipientAddress,
					$recipientName,
					$component,
					$template,
					$componentDeltaSignificant
				);
			}
		}

		return $messages;

	}

	protected function instanceCancelledByOrganizer(Component $instance, VCalendar $template): array {

		$messages = [];
		// instance must contain sender and recipient
		if (!isset($instance->ORGANIZER) || !isset($instance->ATTENDEE)) {
			return $messages;
		}
		$component = clone $instance;
		$component = $this->componentSanitizeScheduling($component);
		$senderAddress = $instance->ORGANIZER->getValue();
		$senderName = $instance->ORGANIZER->parameters()['CN']->getValue() ?? '';

		foreach ($instance->ATTENDEE as $attendee) {
			$recipientAddress = $attendee->getValue();
			$recipientName = $attendee->parameters()['CN']->getValue() ?? '';
			$messages[] = $this->generateMessage(
				'CANCEL',
				$senderAddress,
				$senderName,
				$recipientAddress,
				$recipientName,
				$component,
				$template
			);
		}

		return $messages;

	}

	protected function componentDelta(Component $mutated, ?Component $original = null): array {
		$list = [];
		// construct list of mutated properties
		$propertyNames = $this->componentPropertyNames($mutated);
		foreach ($propertyNames as $propertyName) {
			// properties can be singleton and have multiple instances they need to be handle differently
			if (in_array($propertyName, self::SINGLETON_PROPERTIES, true)) {
				$list[$propertyName] = ['mutated' => $mutated->$propertyName, 'original' => null];
			} else {
				$list[$propertyName] = ['mutated' => $mutated->select($propertyName), 'original' => null];
			}
		}
		if ($original === null) {
			return $list;
		}
		// compare mutated properties with original properties
		$propertyNames = $this->componentPropertyNames($original);
		foreach ($propertyNames as $propertyName) {
			$propertyDelta = false;
			if (isset($list[$propertyName])) {
				// properties can be singleton and have multiple instances they need to be handle differently
				if (in_array($propertyName, self::SINGLETON_PROPERTIES, true)) {
					if ($list[$propertyName]['mutated']->getValue() !== $original->$propertyName->getValue()) {
						$list[$propertyName]['original'] = $original->$propertyName;
						$propertyDelta = true;
					}
				} else {
					$propertyInstances = $original->select($propertyName);
					// determine if any property instances where created or deleted
					if (count($list[$propertyName]['mutated']) !== count($propertyInstances)) {
						$list[$propertyName]['original'] = $propertyInstances;
						$propertyDelta = true;
					}
					// determine if any property instances where modified
					else {
						foreach ($propertyInstances as $originalPropertyInstance) {
							$propertyInstanceDelta = true;
							foreach ($list[$propertyName]['mutated'] as $mutatedPropertyInstance) {
								if ($originalPropertyInstance->getValue() === $mutatedPropertyInstance->getValue()) {
									$propertyInstanceDelta = false;
									break;
								}
							}
							// if even one instance is different mark the entire property as mutated
							if ($propertyInstanceDelta) {
								$list[$propertyName]['original'] = $propertyInstances;
								$propertyDelta = true;
								break;
							}
						}
					}
					unset($propertyInstances, $originalPropertyInstance, $mutatedPropertyInstance);
				}
				// if no changes where detected remove the property from the list
				// otherwise update the list with the original property
				if ($propertyDelta === false) {
					unset($list[$propertyName]);
				}
			} else {
				$list[$propertyName] = ['mutated' => null, 'original' => $original->$propertyName];
			}
		}

		return $list;
	}

	protected function componentPropertyNames(Component $component): array {
		$names = [];
		foreach ($component->children() as $property) {
			$names[strtoupper($property->name)] = true;
		}
		return array_keys($names);
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

	protected function propertyDeltaAttendee(Component $mutated, ?Component $original = null): array {

		$mutatedAttendees = $mutated->select('ATTENDEE');
		$originalAttendees = $original !== null ? $original->select('ATTENDEE') : [];

		$delta = ['Added' => [], 'Removed' => [], 'Extant' => []];
		$delta['Added'] = array_diff($mutatedAttendees, $originalAttendees);
		$delta['Removed'] = array_diff($originalAttendees, $mutatedAttendees);
		$delta['Extant'] = array_diff($mutatedAttendees, $delta['Added'], $delta['Removed']);

		return $delta;
	}

	protected function propertyDeltaExdate(Component $mutated, ?Component $original = null): array {

		$mutatedDates = [];
		$originalDates = [];

		foreach ($mutated->select('EXDATE') as $instance) {
			foreach ($instance->getDateTimes() as $date) {
				$mutatedDates[] = $date->format(DateTimeInterface::W3C);
			}
		}
		if ($original !== null) {
			foreach ($original->select('EXDATE') as $instance) {
				foreach ($instance->getDateTimes() as $date) {
					$originalDates[] = $date->format(DateTimeInterface::W3C);
				}
			}
		}

		$delta = ['Added' => [], 'Removed' => [], 'Extant' => []];
		$delta['Added'] = array_diff($mutatedDates, $originalDates);
		$delta['Removed'] = array_diff($originalDates, $mutatedDates);
		$delta['Extant'] = array_diff($mutatedDates, $delta['Added'], $delta['Removed']);

		return $delta;
	}

	protected function generateMessage(
		string $method,
		string $senderAddress,
		string $senderName,
		string $recipientAddress,
		string $recipientName,
		Component $component,
		VCalendar $template,
		bool $significant = true,
	): Message {
		$instance = clone $component;

		if ($instance->METHOD && $instance->METHOD->getValue() !== $method) {
			$instance->METHOD->setValue($method);
		} else {
			$instance->add('METHOD', $method);
		}
		
		$message = new Message();
		$message->method = $method;
		$message->uid = $instance->UID->getValue();
		$message->component = $instance->name;
		$message->sequence = (int)$instance->SEQUENCE->getValue();
		$message->sender = $senderAddress;
		$message->senderName = $senderName;
		$message->recipient = $recipientAddress;
		$message->recipientName = $recipientName;
		$message->significantChange = $significant;
		$message->message = clone $template;
		$message->message->add($instance);

		return $message;
	}
}
