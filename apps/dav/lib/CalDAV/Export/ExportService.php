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
use OCP\ServerVersion;
use Sabre\VObject\Component;
use Sabre\VObject\Writer;

/**
 * Calendar Export Service
 */
class ExportService {

	public const FORMATS = ['ical', 'jcal', 'xcal'];
	private string $systemVersion;

	public function __construct(ServerVersion $serverVersion) {
		$this->systemVersion = $serverVersion->getVersionString();
	}

	/**
	 * Generates serialized content stream for a calendar and objects based in selected format
	 *
	 * @return Generator<string>
	 */
	public function export(ICalendarExport $calendar, CalendarExportOptions $options): Generator {
		// output start of serialized content based on selected format
		yield $this->exportStart($options->getFormat());
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
					yield $this->exportObject($vComponent, $options->getFormat(), $consecutive);
					$consecutive = true;
				}
			}
		}
		// iterate through each saved vTimezone entry, convert to appropriate format and output
		foreach ($timezones as $vComponent) {
			yield $this->exportObject($vComponent, $options->getFormat(), $consecutive);
			$consecutive = true;
		}
		// output end of serialized content based on selected format
		yield $this->exportFinish($options->getFormat());
	}

	/**
	 * Generates serialized content start based on selected format
	 */
	private function exportStart(string $format): string {
		return match ($format) {
			'jcal' => '["vcalendar",[["version",{},"text","2.0"],["prodid",{},"text","-\/\/IDN nextcloud.com\/\/Calendar Export v' . $this->systemVersion . '\/\/EN"]],[',
			'xcal' => '<?xml version="1.0" encoding="UTF-8"?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0"><vcalendar><properties><version><text>2.0</text></version><prodid><text>-//IDN nextcloud.com//Calendar Export v' . $this->systemVersion . '//EN</text></prodid></properties><components>',
			default => "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//IDN nextcloud.com//Calendar Export v" . $this->systemVersion . "//EN\n"
		};
	}

	/**
	 * Generates serialized content end based on selected format
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
	 */
	private function exportObjectXml(Component $vobject): string {
		$writer = new \Sabre\Xml\Writer();
		$writer->openMemory();
		$writer->setIndent(false);
		$vobject->xmlSerialize($writer);
		return $writer->outputMemory();
	}

}
