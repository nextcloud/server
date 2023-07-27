<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\CalDAV;

use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Collection;

class SystemCalendarRoot extends Collection {

	public function __construct(
		private CalDavBackend $caldavBackend,
		private IL10N $l10n,
		private IConfig $config,
		private LoggerInterface $logger
	) {
	}

	public function getChildren() {
		$calendars = $this->caldavBackend->getCalendarsForUser('principals/system/system');

		$calendarsRet = [];
		foreach ($calendars  as $calendar) {
			$calendarsRet[] = new Calendar($this->caldavBackend, $calendar, $this->l10n, $this->config, $this->logger);
		}

		return $calendarsRet;
	}

	public function getChild($name) {
		$calendars = $this->caldavBackend->getCalendarsForUser('principals/system/system');
		$cal = new Calendar($this->caldavBackend, $calendars[0], $this->l10n, $this->config, $this->logger);
		$cal->setACL([[
			'privilege' => '{DAV:}all',
			'principal' => 'user',
			'protected' => true,
		]]);
			return $cal;
	}

	public function getName() {
		return 'system-calendars';
	}
}
