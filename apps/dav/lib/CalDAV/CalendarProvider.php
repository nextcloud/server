<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CalDAV;

use OCP\Calendar\ICalendarProvider;
use OCP\Calendar\ICreateFromString;
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

	public function __construct(CalDavBackend $calDavBackend, IL10N $l10n, IConfig $config, LoggerInterface $logger) {
		$this->calDavBackend = $calDavBackend;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->logger = $logger;
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
			$calendar = new Calendar($this->calDavBackend, $calendarInfo, $this->l10n, $this->config, $this->logger);
			$iCalendars[] = new CalendarImpl(
				$calendar,
				$calendarInfo,
				$this->calDavBackend,
			);
		}
		return $iCalendars;
	}
}
