<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Import;

use Generator;
use InvalidArgumentException;
use OCA\DAV\CalDAV\CalDavBackend;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

/**
 * Calendar Import Service
 */
class ImportService {

	public function __construct(
		private CalDavBackend $backend,
	) {
	}

	/**
	 * Generates object stream from a text formatted source (ical)
	 *
	 * @param resource $source
	 *
	 * @return Generator<\Sabre\VObject\Component\VCalendar>
	 */
	public function importText($source): Generator {
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
	 * @return Generator<\Sabre\VObject\Component\VCalendar>
	 */
	public function importXml($source): Generator {
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
	 *
	 * @return Generator<\Sabre\VObject\Component\VCalendar>
	 */
	public function importJson($source): Generator {
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
