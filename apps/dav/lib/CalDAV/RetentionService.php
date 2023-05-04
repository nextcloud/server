<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCA\DAV\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use function max;

class RetentionService {
	public const RETENTION_CONFIG_KEY = 'calendarRetentionObligation';
	private const DEFAULT_RETENTION_SECONDS = 30 * 24 * 60 * 60;

	/** @var IConfig */
	private $config;

	/** @var ITimeFactory */
	private $time;

	/** @var CalDavBackend */
	private $calDavBackend;

	public function __construct(IConfig $config,
								ITimeFactory $time,
								CalDavBackend $calDavBackend) {
		$this->config = $config;
		$this->time = $time;
		$this->calDavBackend = $calDavBackend;
	}

	public function getDuration(): int {
		return max(
			(int) $this->config->getAppValue(
				Application::APP_ID,
				self::RETENTION_CONFIG_KEY,
				(string) self::DEFAULT_RETENTION_SECONDS
			),
			0 // Just making sure we don't delete things in the future when a negative number is passed
		);
	}

	public function cleanUp(): void {
		$retentionTime = $this->getDuration();
		$now = $this->time->getTime();

		$calendars = $this->calDavBackend->getDeletedCalendars($now - $retentionTime);
		foreach ($calendars as $calendar) {
			$this->calDavBackend->deleteCalendar($calendar['id'], true);
		}

		$objects = $this->calDavBackend->getDeletedCalendarObjects($now - $retentionTime);
		foreach ($objects as $object) {
			$this->calDavBackend->deleteCalendarObject(
				$object['calendarid'],
				$object['uri'],
				$object['calendartype'],
				true
			);
		}
	}
}
