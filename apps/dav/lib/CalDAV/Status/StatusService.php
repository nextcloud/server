<?php

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

use DateTimeImmutable;
use OC\Calendar\CalendarQuery;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\UserStatus\Service\StatusService as UserStatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\IManager;
use OCP\DB\Exception;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUser as User;
use OCP\IUserManager;
use OCP\User\IAvailabilityCoordinator;
use OCP\UserStatus\IUserStatus;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;

class StatusService {
	private ICache $cache;
	public function __construct(private ITimeFactory $timeFactory,
		private IManager $calendarManager,
		private IUserManager $userManager,
		private UserStatusService $userStatusService,
		private IAvailabilityCoordinator $availabilityCoordinator,
		private ICacheFactory $cacheFactory,
		private LoggerInterface $logger) {
		$this->cache = $cacheFactory->createLocal('CalendarStatusService');
	}

	public function processCalendarStatus(string $userId): void {
		$user = $this->userManager->get($userId);
		if($user === null) {
			return;
		}

		$availability = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		if($availability !== null && $this->availabilityCoordinator->isInEffect($availability)) {
			$this->logger->debug('An Absence is in effect, skipping calendar status check', ['user' => $userId]);
			return;
		}

		$calendarEvents = $this->cache->get($userId);
		if($calendarEvents === null) {
			$calendarEvents = $this->getCalendarEvents($user);
			$this->cache->set($userId, $calendarEvents, 300);
		}

		if(empty($calendarEvents)) {
			try {
				$this->userStatusService->revertUserStatus($userId, IUserStatus::MESSAGE_CALENDAR_BUSY);
			} catch (Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// A different process might have written another status
					// update to the DB while we're processing our stuff.
					// We cannot safely restore the status as we don't know which one is valid at this point
					// So let's silently log this one and exit
					$this->logger->debug('Unique constraint violation for live user status', ['exception' => $e]);
					return;
				}
			}
			$this->logger->debug('No calendar events found for status check', ['user' => $userId]);
			return;
		}

		try {
			$currentStatus = $this->userStatusService->findByUserId($userId);
			// Was the status set by anything other than the calendar automation?
			$userStatusTimestamp = $currentStatus->getIsUserDefined() && $currentStatus->getMessageId() !== IUserStatus::MESSAGE_CALENDAR_BUSY ? $currentStatus->getStatusTimestamp() : null;
		} catch (DoesNotExistException) {
			$userStatusTimestamp = null;
			$currentStatus = null;
		}

		if(($currentStatus !== null && $currentStatus->getMessageId() === IUserStatus::MESSAGE_CALL)
			|| ($currentStatus !== null && $currentStatus->getStatus() === IUserStatus::DND)
			|| ($currentStatus !== null && $currentStatus->getStatus() === IUserStatus::INVISIBLE)) {
			// We don't overwrite the call status, DND status or Invisible status
			$this->logger->debug('Higher priority status detected, skipping calendar status change', ['user' => $userId]);
			return;
		}

		// Filter events to see if we have any that apply to the calendar status
		$applicableEvents = array_filter($calendarEvents, static function (array $calendarEvent) use ($userStatusTimestamp): bool {
			if (empty($calendarEvent['objects'])) {
				return false;
			}
			$component = $calendarEvent['objects'][0];
			if (isset($component['X-NEXTCLOUD-OUT-OF-OFFICE'])) {
				return false;
			}
			if (isset($component['DTSTART']) && $userStatusTimestamp !== null) {
				/** @var DateTimeImmutable $dateTime */
				$dateTime = $component['DTSTART'][0];
				if($dateTime instanceof DateTimeImmutable && $userStatusTimestamp > $dateTime->getTimestamp()) {
					return false;
				}
			}
			// Ignore events that are transparent
			if (isset($component['TRANSP']) && strcasecmp($component['TRANSP'][0], 'TRANSPARENT') === 0) {
				return false;
			}
			return true;
		});

		if(empty($applicableEvents)) {
			try {
				$this->userStatusService->revertUserStatus($userId, IUserStatus::MESSAGE_CALENDAR_BUSY);
			} catch (Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// A different process might have written another status
					// update to the DB while we're processing our stuff.
					// We cannot safely restore the status as we don't know which one is valid at this point
					// So let's silently log this one and exit
					$this->logger->debug('Unique constraint violation for live user status', ['exception' => $e]);
					return;
				}
			}
			$this->logger->debug('No status relevant events found, skipping calendar status change', ['user' => $userId]);
			return;
		}

		// Only update the status if it's neccesary otherwise we mess up the timestamp
		if($currentStatus === null || $currentStatus->getMessageId() !== IUserStatus::MESSAGE_CALENDAR_BUSY) {
			// One event that fulfills all status conditions is enough
			// 1. Not an OOO event
			// 2. Current user status (that is not a calendar status) was not set after the start of this event
			// 3. Event is not set to be transparent
			$count = count($applicableEvents);
			$this->logger->debug("Found $count applicable event(s), changing user status", ['user' => $userId]);
			$this->userStatusService->setUserStatus(
				$userId,
				IUserStatus::AWAY,
				IUserStatus::MESSAGE_CALENDAR_BUSY,
				true
			);
		}
	}

	private function getCalendarEvents(User $user): array {
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $user->getUID());
		if(empty($calendars)) {
			return [];
		}

		$query = $this->calendarManager->newQuery('principals/users/' . $user->getUID());
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
			$query->addSearchCalendar($calendarObject->getUri());
		}

		$dtStart = DateTimeImmutable::createFromMutable($this->timeFactory->getDateTime());
		$dtEnd = DateTimeImmutable::createFromMutable($this->timeFactory->getDateTime('+5 minutes'));

		// Only query the calendars when there's any to search
		if($query instanceof CalendarQuery && !empty($query->getCalendarUris())) {
			// Query the next hour
			$query->setTimerangeStart($dtStart);
			$query->setTimerangeEnd($dtEnd);
			return $this->calendarManager->searchForPrincipal($query);
		}

		return [];
	}
}
