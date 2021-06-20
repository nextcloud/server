<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

use OCA\DAV\CardDAV\Activity\Provider\Card;
use OCA\DAV\CardDAV\Activity\Backend as ActivityBackend;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;
use function sprintf;

class CardListener implements IEventListener {
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
		if ($event instanceof CardCreatedEvent) {
			try {
				$this->activityBackend->triggerCardActivity(
					Card::SUBJECT_ADD,
					$event->getAddressBookData(),
					$event->getShares(),
					$event->getCardData()
				);

				$this->logger->debug(
					sprintf('Activity generated for a new card in addressbook %d', $event->getAddressBookId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the addressbook creation, so we just log it
				$this->logger->error('Error generating activities for a new card in addressbook: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CardUpdatedEvent) {
			try {
				$this->activityBackend->triggerCardActivity(
					Card::SUBJECT_UPDATE,
					$event->getAddressBookData(),
					$event->getShares(),
					$event->getCardData()
				);

				$this->logger->debug(
					sprintf('Activity generated for a changed card in addressbook %d', $event->getAddressBookId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the addressbook update, so we just log it
				$this->logger->error('Error generating activities for a changed card in addressbook: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof CardDeletedEvent) {
			try {
				$this->activityBackend->triggerCardActivity(
					Card::SUBJECT_DELETE,
					$event->getAddressBookData(),
					$event->getShares(),
					$event->getCardData()
				);

				$this->logger->debug(
					sprintf('Activity generated for a deleted card in addressbook %d', $event->getAddressBookId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the addressbook deletion, so we just log it
				$this->logger->error('Error generating activities for a deleted card in addressbook: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}
	}
}
