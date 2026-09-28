<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<CalendarShareUpdatedEvent> */
class CalendarShareUpdateListener implements IEventListener {
	public function __construct(
		private Backend $activityBackend,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * In case the user has set their default calendar to the deleted one
	 */
	public function handle(Event $event): void {
		if (!($event instanceof CalendarShareUpdatedEvent)) {
			// Not what we subscribed to
			return;
		}

		$this->logger->debug('Creating activity for Calendar having its shares updated');

		$this->activityBackend->onCalendarUpdateShares(
			$event->getCalendarData(),
			$event->getOldShares(),
			$event->getAdded(),
			$event->getRemoved()
		);

		// Here we should recalculate if reminders should be sent to new or old sharees
	}
}
