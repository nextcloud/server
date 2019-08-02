<?php
/**
 * @copyright Copyright (c) 2019 Thomas Citharel <tcit@tcit.fr>
 *
 * @author Thomas Citharel <tcit@tcit.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\CalDAV\Reminder;

use OCP\IDBConnection;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * Class Backend
 *
 * @package OCA\DAV\CalDAV\Reminder
 */
class Backend {

	/** @var IDBConnection */
	protected $db;

	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @param IDBConnection $db
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(IDBConnection $db, ITimeFactory $timeFactory) {
		$this->db = $db;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param string $uid
	 * @param string $calendarId
	 * @param string $uri
	 * @param string $type
	 * @param int $notificationDate
	 * @param int $eventStartDate
	 */
	public function insertReminder(string $uid, string $calendarId, string $uri, string $type, int $notificationDate, int $eventStartDate):void {
		$query = $this->db->getQueryBuilder();
		$query->insert('calendar_reminders')
			->values([
				'uid' => $query->createNamedParameter($uid),
				'calendarid' => $query->createNamedParameter($calendarId),
				'objecturi' => $query->createNamedParameter($uri),
				'type' => $query->createNamedParameter($type),
				'notificationdate' => $query->createNamedParameter($notificationDate),
				'eventstartdate' => $query->createNamedParameter($eventStartDate),
			])->execute();
	}

	/**
	 * Cleans reminders in database
	 *
	 * @param int $calendarId
	 * @param string $objectUri
	 */
	public function cleanRemindersForEvent(int $calendarId, string $objectUri):void {
		$query = $this->db->getQueryBuilder();

		$query->delete('calendar_reminders')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->andWhere($query->expr()->eq('objecturi', $query->createNamedParameter($objectUri)))
			->execute();
	}

	/**
	 * Remove all reminders for a calendar
	 *
	 * @param integer $calendarId
	 * @return void
	 */
	public function cleanRemindersForCalendar(int $calendarId):void {
		$query = $this->db->getQueryBuilder();

		$query->delete('calendar_reminders')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->execute();
	}

	/**
	 * Remove a reminder by it's id
	 *
	 * @param integer $reminderId
	 * @return void
	 */
	public function removeReminder(int $reminderId):void {
		$query = $this->db->getQueryBuilder();

		$query->delete('calendar_reminders')
			->where($query->expr()->eq('id', $query->createNamedParameter($reminderId)))
			->execute();
	}

	/**
	 * Get all reminders with a notification date before now
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getRemindersToProcess():array {
		$query = $this->db->getQueryBuilder();
		$fields = ['cr.id', 'cr.calendarid', 'cr.objecturi', 'cr.type', 'cr.notificationdate', 'cr.uid', 'co.calendardata', 'c.displayname'];
		$stmt = $query->select($fields)
			->from('calendar_reminders', 'cr')
			->where($query->expr()->lte('cr.notificationdate', $query->createNamedParameter($this->timeFactory->getTime())))
			->andWhere($query->expr()->gte('cr.eventstartdate', $query->createNamedParameter($this->timeFactory->getTime()))) # We check that DTSTART isn't before
			->leftJoin('cr', 'calendars', 'c', $query->expr()->eq('cr.calendarid', 'c.id'))
			->leftJoin('cr', 'calendarobjects', 'co', $query->expr()->andX($query->expr()->eq('cr.calendarid', 'c.id'), $query->expr()->eq('co.uri', 'cr.objecturi')))
			->execute();

		return $stmt->fetchAll();
	}
}
