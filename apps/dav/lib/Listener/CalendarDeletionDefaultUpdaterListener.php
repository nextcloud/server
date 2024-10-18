<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\Events\CalendarDeletedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @template-implements IEventListener<CalendarDeletedEvent>
 */
class CalendarDeletionDefaultUpdaterListener implements IEventListener {

	public function __construct(
		private IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * In case the user has set their default calendar to the deleted one
	 */
	public function handle(Event $event): void {
		if (!($event instanceof CalendarDeletedEvent)) {
			// Not what we subscribed to
			return;
		}

		try {
			$principalUri = $event->getCalendarData()['principaluri'];
			if (!str_starts_with($principalUri, 'principals/users')) {
				$this->logger->debug('Default calendar needs no update because the deleted calendar does not belong to a user principal');
				return;
			}

			[, $uid] = \Sabre\Uri\split($principalUri);
			$uri = $event->getCalendarData()['uri'];
			if ($this->config->getUserValue($uid, 'dav', 'defaultCalendar') !== $uri) {
				$this->logger->debug('Default calendar needs no update because the deleted calendar is no the user\'s default one');
				return;
			}

			$this->config->deleteUserValue($uid, 'dav', 'defaultCalendar');

			$this->logger->debug('Default user calendar reset');
		} catch (Throwable $e) {
			// Any error with activities shouldn't abort the calendar deletion, so we just log it
			$this->logger->error('Error generating activities for a deleted calendar: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
