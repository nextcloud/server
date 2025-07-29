<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use OCA\DAV\Db\Property;
use OCA\DAV\Db\PropertyMapper;
use OCP\Calendar\ICalendarProvider;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class CalendarProvider implements ICalendarProvider {

	public function __construct(
		private CalDavBackend $calDavBackend,
		private IL10N $l10n,
		private IConfig $config,
		private LoggerInterface $logger,
		private PropertyMapper $propertyMapper,
	) {
	}

	public function getCalendars(string $principalUri, array $calendarUris = []): array {

		$calendarInfos = $this->calDavBackend->getCalendarsForUser($principalUri) ?? [];

		if (!empty($calendarUris)) {
			$calendarInfos = array_filter($calendarInfos, function ($calendar) use ($calendarUris) {
				return in_array($calendar['uri'], $calendarUris);
			});
		}

		$iCalendars = [];
		foreach ($calendarInfos as $calendarInfo) {
			$calendarInfo = array_merge($calendarInfo, $this->getAdditionalProperties($calendarInfo['principaluri'], $calendarInfo['uri']));
			$calendar = new Calendar($this->calDavBackend, $calendarInfo, $this->l10n, $this->config, $this->logger);
			$iCalendars[] = new CalendarImpl(
				$calendar,
				$calendarInfo,
				$this->calDavBackend,
			);
		}
		return $iCalendars;
	}

	public function getAdditionalProperties(string $principalUri, string $calendarUri): array {
		$user = str_replace('principals/users/', '', $principalUri);
		$path = 'calendars/' . $user . '/' . $calendarUri;

		$properties = $this->propertyMapper->findPropertiesByPath($user, $path);

		$list = [];
		foreach ($properties as $property) {
			if ($property instanceof Property) {
				$list[$property->getPropertyname()] = match ($property->getPropertyname()) {
					'{http://owncloud.org/ns}calendar-enabled' => (bool)$property->getPropertyvalue(),
					default => $property->getPropertyvalue()
				};
			}
		}

		return $list;
	}
}
