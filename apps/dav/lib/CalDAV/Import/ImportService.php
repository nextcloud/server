<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Import;

use Exception;
use Generator;
use InvalidArgumentException;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarImpl;
use OCP\Calendar\CalendarImportOptions;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Node;
use Sabre\VObject\Reader;
use Sabre\VObject\UUIDUtil;

/**
 * Calendar Import Service
 */
class ImportService {

	public function __construct(
		private CalDavBackend $backend,
	) {
	}

	/**
	 * Executes import with appropriate object generator based on format
	 *
	 * @param resource $source
	 *
	 * @return Generator<int, ImportEvent>
	 *
	 * @throws \InvalidArgumentException
	 */
	public function import($source, CalendarImpl $calendar, CalendarImportOptions $options): Generator {
		if (!is_resource($source)) {
			throw new InvalidArgumentException('Invalid import source must be a file resource');
		}
		return match ($options->getFormat()) {
			'ical' => $this->importProcess($source, $calendar, $options, $this->importText(...)),
			'jcal' => $this->importProcess($source, $calendar, $options, $this->importJson(...)),
			'xcal' => $this->importProcess($source, $calendar, $options, $this->importXml(...)),
			default => throw new InvalidArgumentException('Invalid import format'),
		};
	}

	/**
	 * Generates object stream from a text formatted source (ical)
	 *
	 * @param resource $source
	 *
	 * @return Generator<int|string, VCalendar|array{VEVENT: int, VTODO: int, VJOURNAL: int}, mixed, void>
	 */
	public function importText($source, ?CalendarImportOptions $options = null): Generator {
		if (!is_resource($source)) {
			throw new InvalidArgumentException('Invalid import source must be a file resource');
		}
		$importer = new TextImporter($source);
		$structure = $importer->structure();
		$sObjectPrefix = $importer::OBJECT_PREFIX;
		$sObjectSuffix = $importer::OBJECT_SUFFIX;
		// calendar properties
		foreach ($structure['VCALENDAR'] as $entry) {
			if (!str_ends_with($entry, "\n") || !str_ends_with($entry, "\r\n")) {
				$sObjectPrefix .= PHP_EOL;
			}
		}
		// calendar time zones
		$timezones = [];
		foreach ($structure['VTIMEZONE'] as $tid => $collection) {
			$instance = $collection[0];
			$sObjectContents = $importer->extract((int)$instance[2], (int)$instance[3]);
			$vObject = Reader::read($sObjectPrefix . $sObjectContents . $sObjectSuffix);
			$timezones[$tid] = clone $vObject->VTIMEZONE;
		}
		// object counts before streaming if requested
		if ($options?->getCounts()) {
			yield 'counts' => [
				'VEVENT' => count($structure['VEVENT']),
				'VTODO' => count($structure['VTODO']),
				'VJOURNAL' => count($structure['VJOURNAL']),
			];
		}
		// calendar components
		// for each component type, construct a full calendar object with all components
		// that match the same UID and appropriate time zones that are used in the components
		foreach (['VEVENT', 'VTODO', 'VJOURNAL'] as $type) {
			foreach ($structure[$type] as $cid => $instances) {
				/** @var array<int,VCalendar> $instances */
				// extract all instances of component and unserialize to object
				$sObjectContents = '';
				foreach ($instances as $instance) {
					$sObjectContents .= $importer->extract($instance[2], $instance[3]);
				}
				/** @var VCalendar $vObject */
				$vObject = Reader::read($sObjectPrefix . $sObjectContents . $sObjectSuffix);
				// add time zones to object
				foreach ($this->findTimeZones($vObject) as $zone) {
					if (isset($timezones[$zone])) {
						$vObject->add(clone $timezones[$zone]);
					}
				}
				yield $vObject;
			}
		}
	}

	/**
	 * Generates object stream from a xml formatted source (xcal)
	 *
	 * @param resource $source
	 *
	 * @return Generator<int|string, VCalendar|array{VEVENT: int, VTODO: int, VJOURNAL: int}, mixed, void>
	 */
	public function importXml($source, ?CalendarImportOptions $options = null): Generator {
		if (!is_resource($source)) {
			throw new InvalidArgumentException('Invalid import source must be a file resource');
		}
		$importer = new XmlImporter($source);
		$structure = $importer->structure();
		$sObjectPrefix = $importer::OBJECT_PREFIX;
		$sObjectSuffix = $importer::OBJECT_SUFFIX;
		// calendar time zones
		$timezones = [];
		foreach ($structure['VTIMEZONE'] as $tid => $collection) {
			$instance = $collection[0];
			$sObjectContents = $importer->extract((int)$instance[2], (int)$instance[3]);
			$vObject = Reader::readXml($sObjectPrefix . $sObjectContents . $sObjectSuffix);
			$timezones[$tid] = clone $vObject->VTIMEZONE;
		}
		// object counts before streaming if requested
		if ($options?->getCounts()) {
			yield 'counts' => [
				'VEVENT' => count($structure['VEVENT']),
				'VTODO' => count($structure['VTODO']),
				'VJOURNAL' => count($structure['VJOURNAL']),
			];
		}
		// calendar components
		// for each component type, construct a full calendar object with all components
		// that match the same UID and appropriate time zones that are used in the components
		foreach (['VEVENT', 'VTODO', 'VJOURNAL'] as $type) {
			foreach ($structure[$type] as $cid => $instances) {
				/** @var array<int,VCalendar> $instances */
				// extract all instances of component and unserialize to object
				$sObjectContents = '';
				foreach ($instances as $instance) {
					$sObjectContents .= $importer->extract($instance[2], $instance[3]);
				}
				/** @var VCalendar $vObject */
				$vObject = Reader::readXml($sObjectPrefix . $sObjectContents . $sObjectSuffix);
				// add time zones to object
				foreach ($this->findTimeZones($vObject) as $zone) {
					if (isset($timezones[$zone])) {
						$vObject->add(clone $timezones[$zone]);
					}
				}
				yield $vObject;
			}
		}
	}

	/**
	 * Generates object stream from a json formatted source (jcal)
	 *
	 * @param resource $source
	 * @param CalendarImportOptions|null $options
	 *
	 * @return Generator<int|string, VCalendar|array{VEVENT: int, VTODO: int, VJOURNAL: int}, mixed, void>
	 */
	public function importJson($source, ?CalendarImportOptions $options = null): Generator {
		if (!is_resource($source)) {
			throw new InvalidArgumentException('Invalid import source must be a file resource');
		}
		/** @var VCALENDAR $importer */
		$importer = Reader::readJson($source);
		// calendar time zones
		$timezones = [];
		foreach ($importer->VTIMEZONE as $timezone) {
			$tzid = $timezone->TZID?->getValue();
			if ($tzid !== null) {
				$timezones[$tzid] = clone $timezone;
			}
		}
		// calendar components
		$baseComponents = $importer->getBaseComponents();
		// object counts before streaming if requested
		if ($options?->getCounts()) {
			/** @var array{VEVENT: int, VTODO: int, VJOURNAL: int} $counts */
			$counts = ['VEVENT' => 0, 'VTODO' => 0, 'VJOURNAL' => 0];
			foreach ($baseComponents as $component) {
				switch ($component->name) {
					case 'VEVENT':
						$counts['VEVENT']++;
						break;
					case 'VTODO':
						$counts['VTODO']++;
						break;
					case 'VJOURNAL':
						$counts['VJOURNAL']++;
						break;
				}
			}
			yield 'counts' => $counts;
		}
		foreach ($baseComponents as $base) {
			$vObject = new VCalendar;
			$vObject->VERSION = clone $importer->VERSION;
			$vObject->PRODID = clone $importer->PRODID;
			// extract all instances of component
			foreach ($importer->getByUID($base->UID->getValue()) as $instance) {
				$vObject->add(clone $instance);
			}
			// add time zones to object
			foreach ($this->findTimeZones($vObject) as $zone) {
				if (isset($timezones[$zone])) {
					$vObject->add(clone $timezones[$zone]);
				}
			}
			yield $vObject;
		}
	}

	/**
	 * Searches through all component properties looking for defined timezones
	 *
	 * @return array<string>
	 */
	private function findTimeZones(VCalendar $vObject): array {
		$timezones = [];
		foreach ($vObject->getComponents() as $vComponent) {
			if ($vComponent->name !== 'VTIMEZONE') {
				foreach (['DTSTART', 'DTEND', 'DUE', 'RDATE', 'EXDATE'] as $property) {
					if (isset($vComponent->$property?->parameters['TZID'])) {
						$tid = $vComponent->$property->parameters['TZID']->getValue();
						$timezones[$tid] = true;
					}
				}
			}
		}
		return array_keys($timezones);
	}

	/**
	 * Import objects
	 *
	 * @since 32.0.0
	 *
	 * @param resource $source
	 * @param CalendarImportOptions $options
	 * @param callable $generator<CalendarImportOptions>: Generator<\Sabre\VObject\Component\VCalendar>
	 *
	 * @return Generator<int, ImportEvent>
	 */
	public function importProcess($source, CalendarImpl $calendar, CalendarImportOptions $options, callable $generator): Generator {
		$calendarId = $calendar->getKey();
		$calendarUri = $calendar->getUri();
		$principalUri = $calendar->getPrincipalUri();
		foreach ($generator($source, $options) as $key => $value) {
			if ($key === 'counts') {
				yield new ImportCountEvent(
					vevent: $value['VEVENT'] ?? 0,
					vtodo: $value['VTODO'] ?? 0,
					vjournal: $value['VJOURNAL'] ?? 0,
				);
				continue;
			}
			$vObject = $value;
			$components = $vObject->getBaseComponents();
			// determine if the object has no base component types
			if (count($components) === 0) {
				$errorMessage = 'One or more objects discovered with no base component types';
				if ($options->getErrors() === $options::ERROR_FAIL) {
					throw new InvalidArgumentException('Error importing calendar data: ' . $errorMessage);
				}
				yield new ImportObjectEvent(
					disposition: ImportDisposition::Error,
					identifier: null,
					errors: [$errorMessage]
				);
				continue;
			}
			// determine if the object has more than one base component type
			// object can have multiple base components with the same uid
			// but we need to make sure they are of the same type
			if (count($components) > 1) {
				$type = $components[0]->name;
				foreach ($components as $entry) {
					if ($type !== $entry->name) {
						$errorMessage = 'One or more objects discovered with multiple base component types';
						if ($options->getErrors() === $options::ERROR_FAIL) {
							throw new InvalidArgumentException('Error importing calendar data: ' . $errorMessage);
						}
						yield new ImportObjectEvent(
							disposition: ImportDisposition::Error,
							identifier: null,
							errors: [$errorMessage]
						);
						continue 2;
					}
				}
			}
			// determine if the object has a uid
			if (!isset($components[0]->UID)) {
				$errorMessage = 'One or more objects discovered without a UID';
				if ($options->getErrors() === $options::ERROR_FAIL) {
					throw new InvalidArgumentException('Error importing calendar data: ' . $errorMessage);
				}
				yield new ImportObjectEvent(
					disposition: ImportDisposition::Error,
					identifier: null,
					errors: [$errorMessage]
				);
				continue;
			}
			$uid = $components[0]->UID->getValue();
			// validate object
			if ($options->getValidate() !== $options::VALIDATE_NONE) {
				$issues = $this->componentValidate($vObject, true, 3);
				if ($options->getValidate() === $options::VALIDATE_SKIP && $issues !== []) {
					yield new ImportObjectEvent(
						disposition: ImportDisposition::Error,
						identifier: $uid,
						errors: $issues
					);
					continue;
				} elseif ($options->getValidate() === $options::VALIDATE_FAIL && $issues !== []) {
					throw new InvalidArgumentException('Error importing calendar data: UID <' . $uid . '> - ' . $issues[0]);
				}
			}
			// create or update object in the data store
			$objectId = $this->backend->getCalendarObjectByUID($principalUri, $uid, $calendarUri);
			$objectData = $vObject->serialize();
			try {
				if ($objectId === null) {
					$objectId = UUIDUtil::getUUID();
					$this->backend->createCalendarObject(
						$calendarId,
						$objectId,
						$objectData
					);
					yield new ImportObjectEvent(
						disposition: ImportDisposition::Created,
						identifier: $uid,
					);
				} else {
					[$cid, $oid] = explode('/', $objectId);
					if ($options->getSupersede()) {
						$this->backend->updateCalendarObject(
							$calendarId,
							$oid,
							$objectData
						);
						yield new ImportObjectEvent(
							disposition: ImportDisposition::Updated,
							identifier: $uid,
						);
					} else {
						yield new ImportObjectEvent(
							disposition: ImportDisposition::Exists,
							identifier: $uid,
						);
					}
				}
			} catch (Exception $e) {
				$errorMessage = $e->getMessage();
				if ($options->getErrors() === $options::ERROR_FAIL) {
					throw new Exception('Error importing calendar data: UID <' . $uid . '> - ' . $errorMessage, 0, $e);
				}
				yield new ImportObjectEvent(
					disposition: ImportDisposition::Error,
					identifier: $uid,
					errors: [$errorMessage]
				);
			}
		}
	}

	/**
	 * Validate a component
	 *
	 * @param VCalendar $vObject
	 * @param bool $repair attempt to repair the component
	 * @param int $level minimum level of issues to return
	 * @return list<mixed>
	 */
	private function componentValidate(VCalendar $vObject, bool $repair, int $level): array {
		// validate component(S)
		$issues = $vObject->validate(Node::PROFILE_CALDAV);
		// attempt to repair
		if ($repair && count($issues) > 0) {
			$issues = $vObject->validate(Node::REPAIR);
		}
		// filter out messages based on level
		$result = [];
		foreach ($issues as $key => $issue) {
			if (isset($issue['level']) && $issue['level'] >= $level) {
				$result[] = $issue['message'];
			}
		}

		return $result;
	}
}
