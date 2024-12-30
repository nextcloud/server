<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Export;

use Generator;
use OCP\Calendar\CalendarExportOptions;
use OCP\Calendar\ICalendarExport;
use Sabre\VObject\Component;
use Sabre\VObject\Writer;

/**
 * Calendar Export Service
 *
 * @since 32.0.0
 */
class ExportService {
	
	public const FORMATS = ['ical', 'jcal', 'xcal'];

	public function __construct() {
	}

	/**
	 * Generates serialized content stream for a calendar and objects based in selected format
	 *
	 * @since 32.0.0
	 */
	public function export(ICalendarExport $calendar, CalendarExportOptions $options): Generator {
		
		yield $this->exportStart($options->format);

		// iterate through each returned vCalendar entry
		// extract each component except timezones, convert to appropriate format and output
		// extract any timezones and save them but do not output
		$timezones = [];
		foreach ($calendar->export($options) as $entry) {
			$consecutive = false;
			foreach ($entry->getComponents() as $vComponent) {
				if ($vComponent->name === 'VTIMEZONE') {
					if (isset($vComponent->TZID) && !isset($timezones[$vComponent->TZID->getValue()])) {
						$timezones[$vComponent->TZID->getValue()] = clone $vComponent;
					}
				} else {
					yield $this->exportObject($vComponent, $options->format, $consecutive);
					$consecutive = true;
				}
			}
		}
		// iterate through each vTimezone entry, convert to appropriate format and output
		foreach ($timezones as $vComponent) {
			yield $this->exportObject($vComponent, $options->format, $consecutive);
			$consecutive = true;
		}

		yield $this->exportFinish($options->format);

	}

	/**
	 * Generates serialized content start based on selected format
	 *
	 * @since 32.0.0
	 */
	private function exportStart(string $format): string {
		return match ($format) {
			'jcal' => '["vcalendar",[["version",{},"text","2.0"],["prodid",{},"text","-\/\/IDN nextcloud.com\/\/Calendar App\/\/EN"]],[',
			'xcal' => '<?xml version="1.0" encoding="UTF-8"?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0"><vcalendar><properties><version><text>2.0</text></version><prodid><text>-//IDN nextcloud.com//Calendar App//EN</text></prodid></properties><components>',
			default => "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//IDN nextcloud.com//Calendar App//EN\n"
		};
	}

	/**
	 * Generates serialized content end based on selected format
	 *
	 * @since 32.0.0
	 */
	private function exportFinish(string $format): string {
		return match ($format) {
			'jcal' => ']]',
			'xcal' => '</components></vcalendar></icalendar>',
			default => "END:VCALENDAR\n"
		};
	}

	/**
	 * Generates serialized content for a component based on selected format
	 *
	 * @since 32.0.0
	 */
	private function exportObject(Component $vobject, string $format, bool $consecutive): string {
		return match ($format) {
			'jcal' => $consecutive ? ',' . Writer::writeJson($vobject) : Writer::writeJson($vobject),
			'xcal' => $this->exportObjectXml($vobject),
			default => Writer::write($vobject)
		};
	}
	
	/**
	 * Generates serialized content for a component in xml format
	 *
	 * @since 32.0.0
	 */
	private function exportObjectXml(Component $vobject): string {
		$writer = new \Sabre\Xml\Writer();
		$writer->openMemory();
		$writer->setIndent(false);
		$vobject->xmlSerialize($writer);
		return $writer->outputMemory();
	}

}
