<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Dav\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCP\ILogger;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCalendars {

	/** @var CalendarAdapter */
	protected $adapter;

	/** @var CalDavBackend */
	private $backend;

	/** @var ILogger */
	private $logger;

	/** @var OutputInterface */
	private $consoleOutput;

	/**
	 * @param CalendarAdapter $adapter
	 * @param CalDavBackend $backend
	 */
	function __construct(CalendarAdapter $adapter,
						 CalDavBackend $backend,
						 ILogger $logger,
						 OutputInterface $consoleOutput = null
	) {
		$this->adapter = $adapter;
		$this->backend = $backend;
		$this->logger = $logger;
		$this->consoleOutput = $consoleOutput;
	}

	/**
	 * @param string $user
	 */
	public function migrateForUser($user) {

		$this->adapter->foreachCalendar($user, function($calendar) use ($user) {
			$principal = "principals/users/$user";
			$calendarByUri = $this->backend->getCalendarByUri($principal, $calendar['uri']);
			if (!is_null($calendarByUri)) {
				return;
			}

			$newId = $this->backend->createCalendar($principal, $calendar['uri'], [
				'{DAV:}displayname' => $calendar['displayname'],
				'{urn:ietf:params:xml:ns:caldav}calendar-description' => $calendar['displayname'],
				'{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => $calendar['timezone'],
				'{http://apple.com/ns/ical/}calendar-order'  => $calendar['calendarorder'],
				'{http://apple.com/ns/ical/}calendar-color'  => $calendar['calendarcolor'],
			]);

			$this->migrateCalendar($calendar['id'], $newId);
			$this->migrateShares($calendar['id'], $newId);
		});
	}

	public function setup() {
		$this->adapter->setup();
	}

	/**
	 * @param int $calendarId
	 * @param int $newCalendarId
	 */
	private function migrateCalendar($calendarId, $newCalendarId) {
		$this->adapter->foreachCalendarObject($calendarId, function($calObject) use ($newCalendarId) {
			try {
				$this->backend->createCalendarObject($newCalendarId, $calObject['uri'], $calObject['calendardata']);
			} catch (\Exception $ex) {
				$eventId = $calObject['id'];
				$calendarId = $calObject['calendarId'];
				$msg = "One event could not be migrated. (id: $eventId, calendarid: $calendarId)";
				$this->logger->logException($ex, ['app' => 'dav', 'message' => $msg]);
				if (!is_null($this->consoleOutput)) {
					$this->consoleOutput->writeln($msg);
				}
			}
		});
	}

	/**
	 * @param int $calendarId
	 * @param int $newCalendarId
	 */
	private function migrateShares($calendarId, $newCalendarId) {
		$shares =$this->adapter->getShares($calendarId);
		if (empty($shares)) {
			return;
		}

		$add = array_map(function($s) {
			$prefix = 'principal:principals/users/';
			if ((int)$s['share_type'] === 1) {
				$prefix = 'principal:principals/groups/';
			}
			return [
				'href' => $prefix . $s['share_with'],
				'readOnly' => !((int)$s['permissions'] === 31)
			];
		}, $shares);

		$newCalendar = $this->backend->getCalendarById($newCalendarId);
		$calendar = new Calendar($this->backend, $newCalendar);
		$this->backend->updateShares($calendar, $add, []);
	}
}
