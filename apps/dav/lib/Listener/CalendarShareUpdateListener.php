<?php

declare(strict_types=1);

/**
 * @copyright 2022 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

class CalendarShareUpdateListener implements IEventListener {
	private Backend $activityBackend;
	private LoggerInterface $logger;

	public function __construct(Backend $activityBackend,
		LoggerInterface $logger) {
		$this->activityBackend = $activityBackend;
		$this->logger = $logger;
	}

	/**
	 * In case the user has set their default calendar to the deleted one
	 */
	public function handle(Event $event): void {
		if (!($event instanceof CalendarShareUpdatedEvent)) {
			// Not what we subscribed to
			return;
		}

		$this->logger->debug("Creating activity for Calendar having its shares updated");

		$this->activityBackend->onCalendarUpdateShares(
			$event->getCalendarData(),
			$event->getOldShares(),
			$event->getAdded(),
			$event->getRemoved()
		);
	}
}
