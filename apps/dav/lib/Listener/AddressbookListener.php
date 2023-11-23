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

use OCA\DAV\CardDAV\Activity\Backend as ActivityBackend;
use OCA\DAV\Events\AddressBookCreatedEvent;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\AddressBookShareUpdatedEvent;
use OCA\DAV\Events\AddressBookUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;
use function sprintf;

class AddressbookListener implements IEventListener {
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
		if ($event instanceof AddressBookCreatedEvent) {
			try {
				$this->activityBackend->onAddressbookCreate(
					$event->getAddressBookData()
				);

				$this->logger->debug(
					sprintf('Activity generated for new addressbook %d', $event->getAddressBookId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the addressbook creation, so we just log it
				$this->logger->error('Error generating activities for a new addressbook: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof AddressBookUpdatedEvent) {
			try {
				$this->activityBackend->onAddressbookUpdate(
					$event->getAddressBookData(),
					$event->getShares(),
					$event->getMutations()
				);

				$this->logger->debug(
					sprintf('Activity generated for changed addressbook %d', $event->getAddressBookId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the addressbook update, so we just log it
				$this->logger->error('Error generating activities for a changed addressbook: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof AddressBookDeletedEvent) {
			try {
				$this->activityBackend->onAddressbookDelete(
					$event->getAddressBookData(),
					$event->getShares()
				);

				$this->logger->debug(
					sprintf('Activity generated for deleted addressbook %d', $event->getAddressBookId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the addressbook deletion, so we just log it
				$this->logger->error('Error generating activities for a deleted addressbook: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		} elseif ($event instanceof AddressBookShareUpdatedEvent) {
			try {
				$this->activityBackend->onAddressbookUpdateShares(
					$event->getAddressBookData(),
					$event->getOldShares(),
					$event->getAdded(),
					$event->getRemoved()
				);

				$this->logger->debug(
					sprintf('Activity generated for (un)sharing addressbook %d', $event->getAddressBookId())
				);
			} catch (Throwable $e) {
				// Any error with activities shouldn't abort the addressbook creation, so we just log it
				$this->logger->error('Error generating activities for (un)sharing addressbook: ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}
	}
}
