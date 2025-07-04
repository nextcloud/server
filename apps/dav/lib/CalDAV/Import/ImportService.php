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
use OCA\DAV\CalDAV\CalendarImpl;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\ICalendarImport;
use Sabre\DAV\UUIDUtil;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

/**
 * Calendar Import Service
 */
class ImportService {
	
	public const FORMATS = ['ical', 'jcal', 'xcal'];

	private $source;

	public function __construct() {
	}

	/**
	 * Executes import with appropriate object generator based on format
	 *
	 * @param resource $source
	 *
	 * @return array<string,array<string,string|array<string>>>
	 *
	 * @throws \InvalidArgumentException
	 */
	public function import($source, ICalendarImport $calendar, CalendarImportOptions $options): array {
		if (!is_resource($source)) {
			throw new \InvalidArgumentException('Invalid import source must be a file resource');
		}
			
		$this->source = $source;

		switch ($options->getFormat()) {
			case 'ical':
				return $this->importProcess($calendar, $options, $this->importText(...));
				break;
			case 'jcal':
				return $this->importProcess($calendar, $options, $this->importJson(...));
				break;
			case 'xcal':
				return $this->importProcess($calendar, $options, $this->importXml(...));
				break;
			default:
				throw new \InvalidArgumentException('Invalid import format');
		}
	}

	private function importProcess(CalendarImpl $calendar, CalendarImportOptions $options, callable $generator): array {
		$calendarId = $calendar->getKey();
		$outcome = [];
		foreach ($generator($options) as $vObject) {
			$components = $vObject->getBaseComponents();
			// determine if the object has no base component types
			if (count($components) === 0) {
				$errorMessage = 'One or more objects discovered with no base component types';
				if ($options->getErrors() === $options::ERROR_FAIL) {
					throw new InvalidArgumentException('Error importing calendar data: ' . $errorMessage);
				}
				$outcome['nbct'] = ['outcome' => 'error', 'errors' => [$errorMessage]];
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
						$outcome['mbct'] = ['outcome' => 'error', 'errors' => [$errorMessage]];
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
				$outcome['noid'] = ['outcome' => 'error', 'errors' => [$errorMessage]];
				continue;
			}
			$uid = (string)$components[0]->UID->getValue();
			// validate object
			if ($options->getValidate() !== $options::VALIDATE_NONE) {
				$issues = $calendar->validateComponent($vObject, true, 3);
				if ($options->getValidate() === $options::VALIDATE_SKIP && $issues !== []) {
					$outcome[$uid] = ['outcome' => 'error', 'errors' => $issues];
					continue;
				} elseif ($options->getValidate() === $options::VALIDATE_FAIL && $issues !== []) {
					throw new InvalidArgumentException('Error importing calendar data: UID <' . $uid . '> - ' . $issues[0]);
				}
			}
			// create or update object in the data store
			//$objectId = $this->backend->getCalendarObjectByUID($this->calendarInfo['principaluri'], $uid);
			$objects = $calendar->search(
				'',
				[],
				['uid' => $uid],
				1
			);
			if (count($objects) > 0) {
				$objectId = $objects[0]['uri'];
			} else {
				$objectId = null;
			}
			$objectData = $vObject->serialize();
			try {
				if ($objectId === null) {
					$objectId = UUIDUtil::getUUID() . '.ics';
					//$this->backend->createCalendarObject(
					//	$calendarId,
					//	$objectId,
					//	$objectData
					//);

					// This is not the best option as it spins up the full dav server and generates iTip/iMip messages
					//$calendar->createFromString($objectId, $vObject->serialize());

					// Create the calendar object in the calendar
					$calendar->createCalendarObject(
						$objectId,
						$objectData
					);

					$outcome[$uid] = ['outcome' => 'created'];
				} elseif ($objectId !== null) {
					//[$cid, $oid] = explode('/', $objectId);
					if ($options->getSupersede()) {
						//$this->backend->updateCalendarObject(
						//	$calendarId,
						//	$oid,
						//	$objectData
						//);

						// Update the calendar object in the calendar
						$calendar->updateCalendarObject(
							$objectId,
							$objectData
						);

						$outcome[$uid] = ['outcome' => 'updated'];
					} else {
						$outcome[$uid] = ['outcome' => 'exists'];
					}
				}
			} catch (Exception $e) {
				$errorMessage = $e->getMessage();
				if ($options->getErrors() === $options::ERROR_FAIL) {
					throw new Exception('Error importing calendar data: UID <' . $uid . '> - ' . $errorMessage, 0, $e);
				}
				$outcome[$uid] = ['outcome' => 'error', 'errors' => [$errorMessage]];
			}
		}

		return $outcome;
	}

	/**
	 * Generates object stream from a text formatted source (ical)
	 *
	 * @return Generator<\Sabre\VObject\Component\VCalendar>
	 */
	private function importText(CalendarImportOptions $options): Generator {
		$importer = new TextImporter($this->source);
		$structure = $importer->structure();
		$sObjectPrefix = $importer::OBJECT_PREFIX;
		$sObjectSuffix = $importer::OBJECT_SUFFIX;
		// calendar properties
		foreach ($structure['VCALENDAR'] as $entry) {
			$sObjectPrefix .= $entry;
			if (substr($entry, -1) !== "\n" || substr($entry, -2) !== "\r\n") {
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
	 * @return Generator<\Sabre\VObject\Component\VCalendar>
	 */
	private function importXml(CalendarImportOptions $options): Generator {
		$importer = new XmlImporter($this->source);
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
	 * @return Generator<\Sabre\VObject\Component\VCalendar>
	 */
	private function importJson(CalendarImportOptions $options): Generator {
		/** @var VCALENDAR $importer */
		$importer = Reader::readJson($this->source);
		// calendar time zones
		$timezones = [];
		foreach ($importer->VTIMEZONE as $timezone) {
			$tzid = $timezone->TZID?->getValue();
			if ($tzid !== null) {
				$timezones[$tzid] = clone $timezone;
			}
		}
		// calendar components
		foreach ($importer->getBaseComponents() as $base) {
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
}
