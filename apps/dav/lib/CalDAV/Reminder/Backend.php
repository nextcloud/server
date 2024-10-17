<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Reminder;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;

/**
 * Class Backend
 *
 * @package OCA\DAV\CalDAV\Reminder
 */
class Backend {

	/**
	 * Backend constructor.
	 *
	 * @param IDBConnection $db
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(
		protected IDBConnection $db,
		protected ITimeFactory $timeFactory,
	) {
	}

	/**
	 * Get all reminders with a notification date before now
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getRemindersToProcess():array {
		$query = $this->db->getQueryBuilder();
		$query->select(['cr.id', 'cr.calendar_id','cr.object_id','cr.is_recurring','cr.uid','cr.recurrence_id','cr.is_recurrence_exception','cr.event_hash','cr.alarm_hash','cr.type','cr.is_relative','cr.notification_date','cr.is_repeat_based','co.calendardata', 'c.displayname', 'c.principaluri'])
			->from('calendar_reminders', 'cr')
			->where($query->expr()->lte('cr.notification_date', $query->createNamedParameter($this->timeFactory->getTime())))
			->join('cr', 'calendarobjects', 'co', $query->expr()->eq('cr.object_id', 'co.id'))
			->join('cr', 'calendars', 'c', $query->expr()->eq('cr.calendar_id', 'c.id'))
			->groupBy('cr.event_hash', 'cr.notification_date', 'cr.type', 'cr.id', 'cr.calendar_id', 'cr.object_id', 'cr.is_recurring', 'cr.uid', 'cr.recurrence_id', 'cr.is_recurrence_exception', 'cr.alarm_hash', 'cr.is_relative', 'cr.is_repeat_based', 'co.calendardata', 'c.displayname', 'c.principaluri');
		$stmt = $query->executeQuery();

		return array_map(
			[$this, 'fixRowTyping'],
			$stmt->fetchAll()
		);
	}

	/**
	 * Get all scheduled reminders for an event
	 *
	 * @param int $objectId
	 * @return array
	 */
	public function getAllScheduledRemindersForEvent(int $objectId):array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('calendar_reminders')
			->where($query->expr()->eq('object_id', $query->createNamedParameter($objectId)));
		$stmt = $query->executeQuery();

		return array_map(
			[$this, 'fixRowTyping'],
			$stmt->fetchAll()
		);
	}

	/**
	 * Insert a new reminder into the database
	 *
	 * @param int $calendarId
	 * @param int $objectId
	 * @param string $uid
	 * @param bool $isRecurring
	 * @param int $recurrenceId
	 * @param bool $isRecurrenceException
	 * @param string $eventHash
	 * @param string $alarmHash
	 * @param string $type
	 * @param bool $isRelative
	 * @param int $notificationDate
	 * @param bool $isRepeatBased
	 * @return int The insert id
	 */
	public function insertReminder(int $calendarId,
		int $objectId,
		string $uid,
		bool $isRecurring,
		int $recurrenceId,
		bool $isRecurrenceException,
		string $eventHash,
		string $alarmHash,
		string $type,
		bool $isRelative,
		int $notificationDate,
		bool $isRepeatBased):int {
		$query = $this->db->getQueryBuilder();
		$query->insert('calendar_reminders')
			->values([
				'calendar_id' => $query->createNamedParameter($calendarId),
				'object_id' => $query->createNamedParameter($objectId),
				'uid' => $query->createNamedParameter($uid),
				'is_recurring' => $query->createNamedParameter($isRecurring ? 1 : 0),
				'recurrence_id' => $query->createNamedParameter($recurrenceId),
				'is_recurrence_exception' => $query->createNamedParameter($isRecurrenceException ? 1 : 0),
				'event_hash' => $query->createNamedParameter($eventHash),
				'alarm_hash' => $query->createNamedParameter($alarmHash),
				'type' => $query->createNamedParameter($type),
				'is_relative' => $query->createNamedParameter($isRelative ? 1 : 0),
				'notification_date' => $query->createNamedParameter($notificationDate),
				'is_repeat_based' => $query->createNamedParameter($isRepeatBased ? 1 : 0),
			])
			->executeStatement();

		return $query->getLastInsertId();
	}

	/**
	 * Sets a new notificationDate on an existing reminder
	 *
	 * @param int $reminderId
	 * @param int $newNotificationDate
	 */
	public function updateReminder(int $reminderId,
		int $newNotificationDate):void {
		$query = $this->db->getQueryBuilder();
		$query->update('calendar_reminders')
			->set('notification_date', $query->createNamedParameter($newNotificationDate))
			->where($query->expr()->eq('id', $query->createNamedParameter($reminderId)))
			->executeStatement();
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
			->executeStatement();
	}

	/**
	 * Cleans reminders in database
	 *
	 * @param int $objectId
	 */
	public function cleanRemindersForEvent(int $objectId):void {
		$query = $this->db->getQueryBuilder();

		$query->delete('calendar_reminders')
			->where($query->expr()->eq('object_id', $query->createNamedParameter($objectId)))
			->executeStatement();
	}

	/**
	 * Remove all reminders for a calendar
	 *
	 * @param int $calendarId
	 * @return void
	 */
	public function cleanRemindersForCalendar(int $calendarId):void {
		$query = $this->db->getQueryBuilder();

		$query->delete('calendar_reminders')
			->where($query->expr()->eq('calendar_id', $query->createNamedParameter($calendarId)))
			->executeStatement();
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function fixRowTyping(array $row): array {
		$row['id'] = (int)$row['id'];
		$row['calendar_id'] = (int)$row['calendar_id'];
		$row['object_id'] = (int)$row['object_id'];
		$row['is_recurring'] = (bool)$row['is_recurring'];
		$row['recurrence_id'] = (int)$row['recurrence_id'];
		$row['is_recurrence_exception'] = (bool)$row['is_recurrence_exception'];
		$row['is_relative'] = (bool)$row['is_relative'];
		$row['notification_date'] = (int)$row['notification_date'];
		$row['is_repeat_based'] = (bool)$row['is_repeat_based'];

		return $row;
	}
}
