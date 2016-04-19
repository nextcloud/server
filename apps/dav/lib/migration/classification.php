<?php

namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUser;

class Classification {

	/**
	 * Classification constructor.
	 *
	 * @param CalDavBackend $calDavBackend
	 */
	public function __construct(CalDavBackend $calDavBackend) {
		$this->calDavBackend = $calDavBackend;
	}

	/**
	 * @param IUser $user
	 */
	public function runForUser($user) {
		$principal = 'principals/users/' . $user->getUID();
		$calendars = $this->calDavBackend->getCalendarsForUser($principal);
		foreach ($calendars as $calendar) {
			$objects = $this->calDavBackend->getCalendarObjects($calendar['id']);
			foreach ($objects as $object) {
				$calObject = $this->calDavBackend->getCalendarObject($calendar['id'], $object['id']);
				$classification = $this->extractClassification($calObject['calendardata']);
				$this->calDavBackend->setClassification($object['id'], $classification);
			}
		}
	}

	/**
	 * @param $calObject
	 */
	protected function extractClassification($calendarData) {
		return $this->calDavBackend->getDenormalizedData($calendarData)['classification'];
	}
}
