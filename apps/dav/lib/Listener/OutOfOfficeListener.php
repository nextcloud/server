<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\DAV\Listener;

use DateTimeImmutable;
use DateTimeZone;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\ServerFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\User\IOutOfOfficeData;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Sabre\VObject\Component\VCalendar;
use function fclose;
use function fopen;
use function fwrite;
use function rewind;

/**
 * @template-implements IEventListener<OutOfOfficeScheduledEvent|OutOfOfficeChangedEvent|OutOfOfficeClearedEvent>
 */
class OutOfOfficeListener implements IEventListener {
	public function __construct(
		private ServerFactory $serverFactory,
		private IConfig $appConfig,
		private TimezoneService $timezoneService,
		private LoggerInterface $logger
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof OutOfOfficeScheduledEvent) {
			$userId = $event->getData()->getUser()->getUID();
			$principal = "principals/users/$userId";
			$calendarNode = $this->getCalendarNode($principal, $userId);
			if ($calendarNode === null) {
				return;
			}
			$tzId = $this->timezoneService->getUserTimezone($userId) ?? $this->timezoneService->getDefaultTimezone();
			$vCalendarEvent = $this->createVCalendarEvent($event->getData(), $tzId);
			$stream = fopen('php://memory', 'rb+');
			try {
				fwrite($stream, $vCalendarEvent->serialize());
				rewind($stream);
				$calendarNode->createFile(
					$this->getEventFileName($event->getData()->getId()),
					$stream,
				);
			} finally {
				fclose($stream);
			}
		} elseif ($event instanceof OutOfOfficeChangedEvent) {
			$userId = $event->getData()->getUser()->getUID();
			$principal = "principals/users/$userId";
			$calendarNode = $this->getCalendarNode($principal, $userId);
			if ($calendarNode === null) {
				return;
			}
			$tzId = $this->timezoneService->getUserTimezone($userId) ?? $this->timezoneService->getDefaultTimezone();
			$vCalendarEvent = $this->createVCalendarEvent($event->getData(), $tzId);
			try {
				$oldEvent = $calendarNode->getChild($this->getEventFileName($event->getData()->getId()));
				$oldEvent->put($vCalendarEvent->serialize());
				return;
			} catch (NotFound) {
				$stream = fopen('php://memory', 'rb+');
				try {
					fwrite($stream, $vCalendarEvent->serialize());
					rewind($stream);
					$calendarNode->createFile(
						$this->getEventFileName($event->getData()->getId()),
						$stream,
					);
				} finally {
					fclose($stream);
				}
			}
		} elseif ($event instanceof OutOfOfficeClearedEvent) {
			$userId = $event->getData()->getUser()->getUID();
			$principal = "principals/users/$userId";
			$calendarNode = $this->getCalendarNode($principal, $userId);
			if ($calendarNode === null) {
				return;
			}
			try {
				$oldEvent = $calendarNode->getChild($this->getEventFileName($event->getData()->getId()));
				$oldEvent->delete();
			} catch (NotFound) {
				// The user must have deleted it or the default calendar changed -> ignore
				return;
			}
		}
	}

	private function getCalendarNode(string $principal, string $userId): ?Calendar {
		$invitationServer = $this->serverFactory->createInviationResponseServer(false);
		$server = $invitationServer->getServer();

		/** @var \OCA\DAV\CalDAV\Plugin $caldavPlugin */
		$caldavPlugin = $server->getPlugin('caldav');
		$calendarHomePath = $caldavPlugin->getCalendarHomeForPrincipal($principal);
		if ($calendarHomePath === null) {
			$this->logger->debug('Principal has no calendar home path');
			return null;
		}
		try {
			/** @var CalendarHome $calendarHome */
			$calendarHome = $server->tree->getNodeForPath($calendarHomePath);
		} catch (NotFound $e) {
			$this->logger->debug('Calendar home not found', [
				'exception' => $e,
			]);
			return null;
		}
		$uri = $this->appConfig->getUserValue($userId, 'dav', 'defaultCalendar', CalDavBackend::PERSONAL_CALENDAR_URI);
		try {
			$calendarNode = $calendarHome->getChild($uri);
		} catch (NotFound $e) {
			$this->logger->debug('Personal calendar does not exist', [
				'exception' => $e,
				'uri' => $uri,
			]);
			return null;
		}
		if (!($calendarNode instanceof Calendar)) {
			$this->logger->warning('Personal calendar node is not a calendar');
			return null;
		}
		if ($calendarNode->isDeleted()) {
			$this->logger->warning('Personal calendar has been deleted');
			return null;
		}

		return $calendarNode;
	}

	private function getEventFileName(string $id): string {
		return "out_of_office_$id.ics";
	}

	private function createVCalendarEvent(IOutOfOfficeData $data, string $tzId): VCalendar {
		$shortMessage = $data->getShortMessage();
		$longMessage = $data->getMessage();
		$start = (new DateTimeImmutable)
			->setTimezone(new DateTimeZone($tzId))
			->setTimestamp($data->getStartDate())
			->setTime(0, 0);
		$end = (new DateTimeImmutable())
			->setTimezone(new DateTimeZone($tzId))
			->setTimestamp($data->getEndDate())
			->modify('+ 1 days')
			->setTime(0, 0);
		$vCalendar = new VCalendar();
		$vCalendar->add('VEVENT', [
			'SUMMARY' => $shortMessage,
			'DESCRIPTION' => $longMessage,
			'STATUS' => 'CONFIRMED',
			'DTSTART' => $start,
			'DTEND' => $end,
			'X-NEXTCLOUD-OUT-OF-OFFICE' => $data->getId(),
		]);
		return $vCalendar;
	}
}
