<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\CalDAV;

use OCP\IConfig;
use Sabre\CalDAV\Exception\InvalidComponentType;
use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;
use Sabre\VObject;

class Plugin extends \Sabre\CalDAV\Plugin {
	public const SYSTEM_CALENDAR_ROOT = 'system-calendars';

	public function __construct(private ?IConfig $config = null) {
	}

	/**
	 * Returns the path to a principal's calendar home.
	 *
	 * The return url must not end with a slash.
	 * This function should return null in case a principal did not have
	 * a calendar home.
	 *
	 * @param string $principalUrl
	 * @return string|null
	 */
	#[\Override]
	public function getCalendarHomeForPrincipal($principalUrl) {
		if (strrpos($principalUrl, 'principals/users', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::CALENDAR_ROOT . '/' . $principalId;
		}
		if (strrpos($principalUrl, 'principals/calendar-resources', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::SYSTEM_CALENDAR_ROOT . '/calendar-resources/' . $principalId;
		}
		if (strrpos($principalUrl, 'principals/calendar-rooms', -strlen($principalUrl)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principalUrl);
			return self::SYSTEM_CALENDAR_ROOT . '/calendar-rooms/' . $principalId;
		}
	}

	#[\Override]
	protected function validateICalendar(&$data, $path, &$modified, RequestInterface $request, ResponseInterface $response, $isNew) {
		if (is_resource($data)) {
			$data = stream_get_contents($data);
		}

		$before = $data;

		try {
			if ('[' === substr($data, 0, 1)) {
				$vobj = VObject\Reader::readJson($data);
				$data = $vobj->serialize();
				$modified = true;
			} else {
				$vobj = VObject\Reader::read($data, $this->getReaderOptions());
			}
		} catch (VObject\ParseException $e) {
			throw new DAV\Exception\UnsupportedMediaType('This resource only supports valid iCalendar 2.0 data. Parse error: ' . $e->getMessage());
		}

		if ('VCALENDAR' !== $vobj->name) {
			throw new DAV\Exception\UnsupportedMediaType('This collection can only support iCalendar objects.');
		}

		$sCCS = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';

		list($parentPath) = Uri\split($path);
		$calendarProperties = $this->server->getProperties($parentPath, [$sCCS]);

		if (isset($calendarProperties[$sCCS])) {
			$supportedComponents = $calendarProperties[$sCCS]->getValue();
		} else {
			$supportedComponents = ['VJOURNAL', 'VTODO', 'VEVENT'];
		}

		$foundType = null;

		foreach ($vobj->getComponents() as $component) {
			switch ($component->name) {
				case 'VTIMEZONE':
					continue 2;
				case 'VEVENT':
				case 'VTODO':
				case 'VJOURNAL':
					$foundType = $component->name;
					break;
			}
		}

		if (!$foundType || !in_array($foundType, $supportedComponents)) {
			throw new InvalidComponentType('iCalendar objects must at least have a component of type ' . implode(', ', $supportedComponents));
		}

		$options = VObject\Node::PROFILE_CALDAV;
		$prefer = $this->server->getHTTPPrefer();

		if ('strict' !== $prefer['handling']) {
			$options |= VObject\Node::REPAIR;
		}

		$messages = $vobj->validate($options);

		$highestLevel = 0;
		$warningMessage = null;

		foreach ($messages as $message) {
			if ($message['level'] > $highestLevel) {
				$highestLevel = $message['level'];
				$warningMessage = $message['message'];
			}
			switch ($message['level']) {
				case 1:
					$modified = true;
					break;
				case 2:
					break;
				case 3:
					throw new DAV\Exception\UnsupportedMediaType('Validation error in iCalendar: ' . $message['message']);
			}
		}

		if ($warningMessage) {
			$response->setHeader('X-Sabre-Ew-Gross', 'iCalendar validation warning: ' . $warningMessage);
		}

		$subModified = false;

		$this->server->emit(
			'calendarObjectChange',
			[
				$request,
				$response,
				$vobj,
				$parentPath,
				&$subModified,
				$isNew,
			]
		);

		if ($modified || $subModified) {
			$data = $vobj->serialize();
			if (!$modified && 0 !== strcmp($data, $before)) {
				$modified = true;
			}
		}

		$vobj->destroy();
	}

	private function getReaderOptions(): int {
		return $this->config?->getSystemValueBool('dav.forgiving_ical_parser', false)
			? VObject\Reader::OPTION_FORGIVING
			: 0;
	}
}
