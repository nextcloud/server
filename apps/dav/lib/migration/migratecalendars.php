<?php

namespace OCA\Dav\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCalendars {

	/** @var CalendarAdapter */
	protected $adapter;

	/** @var CalDavBackend */
	private $backend;

	/**
	 * @param CalendarAdapter $adapter
	 * @param CalDavBackend $backend
	 */
	function __construct(CalendarAdapter $adapter,
						 CalDavBackend $backend
	) {
		$this->adapter = $adapter;
		$this->backend = $backend;
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
			$this->backend->createCalendarObject($newCalendarId, $calObject['uri'], $calObject['calendardata']);
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
			if ($s['share_type'] === 1) {
				$prefix = 'principal:principals/groups/';
			}
			return [
				'href' => $prefix . $s['share_with']
			];
		}, $shares);

		$newCalendar = $this->backend->getCalendarById($newCalendarId);
		$calendar = new Calendar($this->backend, $newCalendar);
		$this->backend->updateShares($calendar, $add, []);
	}
}
