<?php
/*
 * *
 *  * Dav App
 *  *
 *  * @copyright 2023 Anna Larch <anna.larch@gmx.net>
 *  *
 *  * @author Anna Larch <anna.larch@gmx.net>
 *  *
 *  * This library is free software; you can redistribute it and/or
 *  * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *  * License as published by the Free Software Foundation; either
 *  * version 3 of the License, or any later version.
 *  *
 *  * This library is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public
 *  * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *  *
 *
 */

declare(strict_types=1);

/**
 * @copyright 2023 Anna Larch <anna.larch@gmx.net>
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
namespace OCA\DAV\CalDAV\Status;

use DateTimeZone;
use OC\Calendar\CalendarQuery;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\FreeBusy\FreeBusyGenerator;
use OCA\DAV\CalDAV\InvitationResponse\InvitationResponseServer;
use OCA\DAV\CalDAV\IUser;
use OCA\DAV\CalDAV\Schedule\Plugin as SchedulePlugin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\IManager;
use OCP\Calendar\ISchedulingInformation;
use OCP\IL10N;
use OCP\IUser as User;
use OCP\UserStatus\IUserStatus;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAVACL\Exception\NeedPrivileges;
use Sabre\DAVACL\Plugin as AclPlugin;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class StatusService {
	public function __construct(private ITimeFactory $timeFactory,
								private IManager $calendarManager,
								private InvitationResponseServer $server,
								private IL10N $l10n,
								private FreeBusyGenerator $generator){}

	public function processCalendarAvailability(User $user, ?string $availability): ?Status {
		$userId = $user->getUID();
		$email = $user->getEMailAddress();
		if($email === null) {
			return null;
		}

		$server = $this->server->getServer();

		/** @var SchedulePlugin $schedulingPlugin */
		$schedulingPlugin = $server->getPlugin('caldav-schedule');
		$caldavNS = '{'.$schedulingPlugin::NS_CALDAV.'}';

		/** @var AclPlugin $aclPlugin */
		$aclPlugin = $server->getPlugin('acl');
		if ('mailto:' === substr($email, 0, 7)) {
			$email = substr($email, 7);
		}

		$result = $aclPlugin->principalSearch(
			['{http://sabredav.org/ns}email-address' => $email],
			[
				'{DAV:}principal-URL',
				$caldavNS.'calendar-home-set',
				$caldavNS.'schedule-inbox-URL',
				'{http://sabredav.org/ns}email-address',
			]
		);

		if (!count($result) || !isset($result[0][200][$caldavNS.'schedule-inbox-URL'])) {
			return null;
		}

		$inboxUrl = $result[0][200][$caldavNS.'schedule-inbox-URL']->getHref();

		// Do we have permission?
		try {
			$aclPlugin->checkPrivileges($inboxUrl, $caldavNS.'schedule-query-freebusy');
		} catch (NeedPrivileges | NotAuthenticated $exception) {
			return null;
		}

		$now = $this->timeFactory->now();
		$calendarTimeZone = $now->getTimezone();
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId);
		if(empty($calendars)) {
			return null;
		}

		$query = $this->calendarManager->newQuery('principals/users/' . $userId);
		foreach ($calendars as $calendarObject) {
			// We can only work with a calendar if it exposes its scheduling information
			if (!$calendarObject instanceof CalendarImpl) {
				continue;
			}

			$sct = $calendarObject->getSchedulingTransparency();
			if ($sct !== null && ScheduleCalendarTransp::TRANSPARENT == strtolower($sct->getValue())) {
				// If a calendar is marked as 'transparent', it means we must
				// ignore it for free-busy purposes.
				continue;
			}

			/** @var Component\VTimeZone|null $ctz */
			$ctz = $calendarObject->getSchedulingTimezone();
			if ($ctz !== null) {
				$calendarTimeZone = $ctz->getTimeZone();
			}
			$query->addSearchCalendar($calendarObject->getUri());
		}

		$calendarEvents = [];
		$dtStart = $now;
		$dtEnd = \DateTimeImmutable::createFromMutable($this->timeFactory->getDateTime('+10 minutes'));

		// Only query the calendars when there's any to search
		if($query instanceof CalendarQuery && !empty($query->getCalendarUris())) {
			// Query the next hour
			$query->setTimerangeStart($dtStart);
			$query->setTimerangeEnd($dtEnd);
			$calendarEvents = $this->calendarManager->searchForPrincipal($query);
		}

		// @todo we can cache that
		if(empty($availability) && empty($calendarEvents)) {
			// No availability settings and no calendar events, we can stop here
			return null;
		}

		$calendar = $this->generator->getVCalendar();
		foreach ($calendarEvents as $calendarEvent) {
			$vEvent = new VEvent($calendar, 'VEVENT');
			foreach($calendarEvent['objects'] as $component) {
				foreach ($component as $key =>  $value) {
					$vEvent->add($key, $value[0]);
				}
			}
			$calendar->add($vEvent);
		}

		$calendar->METHOD = 'REQUEST';

		$this->generator->setObjects($calendar);
		$this->generator->setTimeRange($dtStart, $dtEnd);
		$this->generator->setTimeZone($calendarTimeZone);

		if (!empty($availability)) {
			$this->generator->setVAvailability(
				Reader::read(
					$availability
				)
			);
		}
		// Generate the intersection of VAVILABILITY and all VEVENTS in all calendars
		$result = $this->generator->getResult();

		if (!isset($result->VFREEBUSY)) {
			return null;
		}

		/** @var Component $freeBusyComponent */
		$freeBusyComponent = $result->VFREEBUSY;
		$freeBusyProperties = $freeBusyComponent->select('FREEBUSY');
		// If there is no FreeBusy property, the time-range is empty and available
		// so set the status to online as otherwise we will never recover from a BUSY status
		if (count($freeBusyProperties) === 0) {
			return new Status(IUserStatus::ONLINE);
		}

		/** @var Property $freeBusyProperty */
		$freeBusyProperty = $freeBusyProperties[0];
		if (!$freeBusyProperty->offsetExists('FBTYPE')) {
			// If there is no FBTYPE, it means it's busy from a regular event
			return new Status(IUserStatus::BUSY, IUserStatus::MESSAGE_CALENDAR_BUSY);
		}

		// If we can't deal with the FBTYPE (custom properties are a possibility)
		// we should ignore it and leave the current status
		$fbTypeParameter = $freeBusyProperty->offsetGet('FBTYPE');
		if (!($fbTypeParameter instanceof Parameter)) {
			return null;
		}
		$fbType = $fbTypeParameter->getValue();
		switch ($fbType) {
			case 'BUSY':
				return new Status(IUserStatus::BUSY, IUserStatus::MESSAGE_CALENDAR_BUSY, $this->l10n->t('In a meeting'));
			case 'BUSY-UNAVAILABLE':
				return new Status(IUserStatus::AWAY, IUserStatus::MESSAGE_AVAILABILITY);
			case 'BUSY-TENTATIVE':
				return new Status(IUserStatus::AWAY, IUserStatus::MESSAGE_CALENDAR_BUSY_TENTATIVE);
			default:
				return null;
		}
	}
}
