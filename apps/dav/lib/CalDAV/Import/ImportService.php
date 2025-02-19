<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Import;

use Generator;
use OCP\Calendar\CalendarImportOptions;
use OCP\Calendar\ICalendarImport;
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
				return $calendar->import($options, $this->importText(...));
				break;
			case 'jcal':
				return $calendar->import($options, $this->importJson(...));
				break;
			case 'xcal':
				return $calendar->import($options, $this->importXml(...));
				break;
			default:
				throw new \InvalidArgumentException('Invalid import format');
		}
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
