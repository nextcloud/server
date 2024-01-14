<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCA\DAV\Events\CalendarDeletedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @template-implements IEventListener<\OCA\DAV\Events\CalendarDeletedEvent>
 */
class CalendarDeletionDefaultUpdaterListener implements IEventListener {

	/** @var IConfig */
	private $config;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IConfig $config,
		LoggerInterface $logger) {
		$this->config = $config;
		$this->logger = $logger;
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
