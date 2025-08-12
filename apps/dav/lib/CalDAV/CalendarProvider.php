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

		$additionalProperties = $this->getAdditionalPropertiesForCalendars($calendarInfos);
		$iCalendars = [];
		foreach ($calendarInfos as $calendarInfo) {
			$user = str_replace('principals/users/', '', $calendarInfo['principaluri']);
			$path = 'calendars/' . $user . '/' . $calendarInfo['uri'];

			$calendarInfo = array_merge($calendarInfo, $additionalProperties[$path] ?? []);

			$calendar = new Calendar($this->calDavBackend, $calendarInfo, $this->l10n, $this->config, $this->logger);
			$iCalendars[] = new CalendarImpl(
				$calendar,
				$calendarInfo,
				$this->calDavBackend,
			);
		}
		return $iCalendars;
	}

	/**
	 * @param array{
	 *     principaluri: string,
	 *     uri: string,
	 * }[] $uris
	 * @return array<string, array<string, string|bool>>
	 */
	private function getAdditionalPropertiesForCalendars(array $uris): array {
		$calendars = [];
		foreach ($uris as $uri) {
			/** @var string $user */
			$user = str_replace('principals/users/', '', $uri['principaluri']);
			if (!array_key_exists($user, $calendars)) {
				$calendars[$user] = [];
			}
			$calendars[$user][] = 'calendars/' . $user . '/' . $uri['uri'];
		}

		$properties = $this->propertyMapper->findPropertiesByPaths($calendars);

		$list = [];
		foreach ($properties as $property) {
			if ($property instanceof Property) {
				if (!isset($list[$property->getPropertypath()])) {
					$list[$property->getPropertypath()] = [];
				}

				$list[$property->getPropertypath()][$property->getPropertyname()] = match ($property->getPropertyname()) {
					'{http://owncloud.org/ns}calendar-enabled' => (bool)$property->getPropertyvalue(),
					default => $property->getPropertyvalue()
				};
			}
		}

		return $list;
	}
}
