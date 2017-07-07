<?php

namespace OCA\DAV\Circles;

use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\SharingFrame;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\Connector\Sabre\Principal;
use Sabre\CalDAV\Plugin;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

class CalDAVSharesBroadcaster implements IBroadcaster {

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

		try {
			$payload = $frame->getPayload();

			// updating the circleId within the href/uri
			$payload['add']['href'] = 'principal:principals/circles/' . $frame->getCircleId();


			$userPrincipalBackend = new Principal(
				\OC::$server->getUserManager(),
				\OC::$server->getGroupManager()
			);

			$caldavBackend = new CalDavBackend(\OC::$server->getDatabaseConnection(),
				$userPrincipalBackend, \OC::$server->getUserManager(), \OC::$server->getSecureRandom(), \OC::$server->getEventDispatcher());


			$calData = $payload['calendar'];

			$k = '{' . Plugin::NS_CALDAV . '}supported-calendar-component-set';
			$calData[$k] = new SupportedCalendarComponentSet($calData[$k]);

			$k = '{' . Plugin::NS_CALDAV . '}schedule-calendar-transp';
			$calData[$k] = new ScheduleCalendarTransp($calData[$k]);


			// In case of Shares from a Federated Circle
			if (!$frame->isLocal()) {

				// define principalUri of the 'fake' calendar
				$principalUri = "principals/circles/" . $frame->getHeader('circleUniqueId') . '.' . $calData['id'];

				// check if calendar exists
				$localCalendar = $caldavBackend->getCalendarsForUser($principalUri);

				if (sizeof($localCalendar) === 0) {
					// create fake calendar
					$calendarId = $caldavBackend->createCalendar($principalUri, $calData['uri'], $calData);
					$calData['id'] = $calendarId;
				} else {
					// or get calendar from DB
					$calData = array_shift($localCalendar);
				}
			}

			$calendar = new Calendar($caldavBackend, $calData, \OC::$server->getL10N('calendar'));
			$caldavBackend->updateShares($calendar, [$payload['add']], []);

			return true;
		} catch (\Exception $e) {
			return false;
		}
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