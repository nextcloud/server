<?php

namespace OCA\DAV\Circles;

use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\SharingFrame;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Connector\Sabre\Principal;

class CalDAVEventsBroadcaster implements IBroadcaster {


	/**
	 * {@inheritdoc}
	 */
	public function init() {
	}

	/**
	 * {@inheritdoc}
	 */
	public function createShareToUser(SharingFrame $frame, $userId) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function createShareToCircle(SharingFrame $frame) {

		if ($frame->isLocal()) {
			return true;
		}

		$payload = $frame->getPayload();

		$userPrincipalBackend = new Principal(
			\OC::$server->getUserManager(),
			\OC::$server->getGroupManager()
		);

		$caldavBackend = new CalDavBackend(\OC::$server->getDatabaseConnection(),
			$userPrincipalBackend, \OC::$server->getUserManager(), \OC::$server->getSecureRandom(), \OC::$server->getEventDispatcher());

		// define principalUri of the 'fake' calendar
		$principalUri = "principals/circles/" . $frame->getHeader('circleUniqueId') . '.' . $payload['calendarId'];

		// check if calendar exists and stop if not
		$localCalendar = $caldavBackend->getCalendarsForUser($principalUri);
		if (sizeof($localCalendar) === 0) {
			return true;
		}

		$calData = array_shift($localCalendar);
		$caldavBackend->createCalendarObject($calData['id'], $payload['objectUri'], $payload['calendarData']);

		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToCircle(SharingFrame $frame) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToCircle(SharingFrame $frame) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToUser(SharingFrame $frame, $userId) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToUser(SharingFrame $frame, $userId) {
		return true;
	}

}