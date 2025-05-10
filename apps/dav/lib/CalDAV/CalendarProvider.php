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

	/** @var CalDavBackend */
	private $calDavBackend;

	/** @var IL10N */
	private $l10n;

	/** @var IConfig */
	private $config;

	/** @var LoggerInterface */
	private $logger;

	/** @var PropertyMapper */
	private $propertyMapper;

	public function __construct(CalDavBackend $calDavBackend, IL10N $l10n, IConfig $config, LoggerInterface $logger, PropertyMapper $propertyMapper) {
		$this->calDavBackend = $calDavBackend;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->logger = $logger;
		$this->propertyMapper = $propertyMapper;
	}

	public function getCalendars(string $principalUri, array $calendarUris = []): array {
		$calendarInfos = [];
		if (empty($calendarUris)) {
			$calendarInfos = $this->calDavBackend->getCalendarsForUser($principalUri);
		} else {
			foreach ($calendarUris as $calendarUri) {
				$calendarInfos[] = $this->calDavBackend->getCalendarByUri($principalUri, $calendarUri);
			}
		}

		$calendarInfos = array_filter($calendarInfos);

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
					'{http://owncloud.org/ns}calendar-enabled' => (bool) $property->getPropertyvalue(),
					default => $property->getPropertyvalue()
				};
			}
		}

		return $list;
	}
}
