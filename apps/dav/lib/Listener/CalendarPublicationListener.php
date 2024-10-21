<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\Events\CalendarPublishedEvent;
use OCA\DAV\Events\CalendarUnpublishedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<CalendarPublishedEvent|CalendarUnpublishedEvent> */
class CalendarPublicationListener implements IEventListener {
	public function __construct(
		private Backend $activityBackend,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * In case the user has set their default calendar to the deleted one
	 */
	public function handle(Event $event): void {
		if ($event instanceof CalendarPublishedEvent) {
			$this->logger->debug('Creating activity for Calendar being published');

			$this->activityBackend->onCalendarPublication(
				$event->getCalendarData(),
				true
			);
		} elseif ($event instanceof CalendarUnpublishedEvent) {
			$this->logger->debug('Creating activity for Calendar being unpublished');

			$this->activityBackend->onCalendarPublication(
				$event->getCalendarData(),
				false
			);
		}
	}
}
