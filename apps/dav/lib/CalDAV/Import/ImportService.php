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
 *
 * @since 32.0.0
 */
class ImportService {
	
	public const FORMATS = ['ical', 'jcal', 'xcal'];

	private $source;

	public function __construct() {
	}

	/**
	 * Executes import with appropriate object generator based on format
	 *
	 * @since 32.0.0
	 */
	public function import($source, ICalendarImport $calendar, CalendarImportOptions $options): array {

		$this->source = $source;

		switch ($options->format) {
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
	 * @since 32.0.0
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
			$sObjectContents = $importer->extract($instance[2], $instance[3]);
			$vObject = Reader::read($sObjectPrefix . $sObjectContents . $sObjectSuffix);
			$timezones[$tid] = clone $vObject->VTIMEZONE;
		}

		// calendar components
		foreach (['VEVENT', 'VTODO', 'VJOURNAL'] as $type) {
			foreach ($structure[$type] as $cid => $instances) {
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
				// return object
				yield $vObject;

			}
		}
	}

	/**
	 * Generates object stream from a xml formatted source (xcal)
	 *
	 * @since 32.0.0
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
			$sObjectContents = $importer->extract($instance[2], $instance[3]);
			$vObject = Reader::readXml($sObjectPrefix . $sObjectContents . $sObjectSuffix);
			$timezones[$tid] = clone $vObject->VTIMEZONE;
		}

		// calendar components
		foreach (['VEVENT', 'VTODO', 'VJOURNAL'] as $type) {
			foreach ($structure[$type] as $cid => $instances) {
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
				// return object
				yield $vObject;

			}
		}

	}

	/**
	 * Generates object stream from a json formatted source (jcal)
	 *
	 * @since 32.0.0
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
			/** @var VCalendar $vObject */
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
			// return object
			yield $vObject;

		}
	}

	/**
	 * Searches through all component properties looking for defined timezones
	 *
	 * @since 32.0.0
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
