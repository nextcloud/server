<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCA\DAV\CalDAV\Activity\Backend as ActivityBackend;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;
use function sprintf;

/**
 * @template-implements IEventListener<\OCA\DAV\Events\CalendarDeletedEvent>
 */
class CalendarDeletionActivityUpdaterListener implements IEventListener {

	/** @var ActivityBackend */
	private $activityBackend;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(ActivityBackend $activityBackend,
								LoggerInterface $logger) {
		$this->activityBackend = $activityBackend;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if (!($event instanceof CalendarDeletedEvent)) {
			// Not what we subscribed to
			return;
		}

		try {
			$this->activityBackend->onCalendarDelete(
				$event->getCalendarData(),
				$event->getShares()
			);

			$this->logger->debug(
				sprintf('Activity generated for deleted calendar %d', $event->getCalendarId())
			);
		} catch (Throwable $e) {
			// Any error with activities shouldn't abort the calendar deletion, so we just log it
			$this->logger->error('Error generating activities for a deleted calendar: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
