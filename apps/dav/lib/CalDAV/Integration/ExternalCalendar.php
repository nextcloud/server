<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Integration;

use Sabre\CalDAV;
use Sabre\DAV;

/**
 * Class ExternalCalendar
 *
 * @package OCA\DAV\CalDAV\Integration
 * @since 19.0.0
 */
abstract class ExternalCalendar implements CalDAV\ICalendar, DAV\IProperties {

	/** @var string */
	private const PREFIX = 'app-generated';

	/**
	 * @var string
	 *
	 * Double dash is a valid delimiter,
	 * because it will always split the calendarURIs correctly:
	 * - our prefix contains only one dash and won't be split
	 * - appIds are not allowed to contain dashes as per spec:
	 * > must contain only lowercase ASCII characters and underscore
	 * - explode has a limit of three, so even if the app-generated
	 *   calendar uri has double dashes, it won't be split
	 */
	private const DELIMITER = '--';

	/**
	 * ExternalCalendar constructor.
	 *
	 * @param string $appId
	 * @param string $calendarUri
	 */
	public function __construct(
		private string $appId,
		private string $calendarUri,
	) {
	}

	/**
	 * @inheritDoc
	 */
	final public function getName() {
		return implode(self::DELIMITER, [
			self::PREFIX,
			$this->appId,
			$this->calendarUri,
		]);
	}

	/**
	 * @inheritDoc
	 */
	final public function setName($name) {
		throw new DAV\Exception\MethodNotAllowed('Renaming calendars is not yet supported');
	}

	/**
	 * @inheritDoc
	 */
	final public function createDirectory($name) {
		throw new DAV\Exception\MethodNotAllowed('Creating collections in calendar objects is not allowed');
	}

	/**
	 * Checks whether the calendar uri is app-generated
	 *
	 * @param string $calendarUri
	 * @return bool
	 */
	public static function isAppGeneratedCalendar(string $calendarUri):bool {
		return str_starts_with($calendarUri, self::PREFIX) && substr_count($calendarUri, self::DELIMITER) >= 2;
	}

	/**
	 * Splits an app-generated calendar-uri into appId and calendarUri
	 *
	 * @param string $calendarUri
	 * @return array
	 */
	public static function splitAppGeneratedCalendarUri(string $calendarUri):array {
		$array = array_slice(explode(self::DELIMITER, $calendarUri, 3), 1);
		// Check the array has expected amount of elements
		// and none of them is an empty string
		if (\count($array) !== 2 || \in_array('', $array, true)) {
			throw new \InvalidArgumentException('Provided calendar uri was not app-generated');
		}

		return $array;
	}

	/**
	 * Checks whether a calendar-name, the user wants to create, violates
	 * the reserved name for calendar uris
	 *
	 * @param string $calendarUri
	 * @return bool
	 */
	public static function doesViolateReservedName(string $calendarUri):bool {
		return str_starts_with($calendarUri, self::PREFIX);
	}
}
