<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\CardDAV\Activity\Backend as ActivityBackend;
use OCA\DAV\CardDAV\Activity\Provider\Card;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;
use function sprintf;

/** @template-implements IEventListener<CardCreatedEvent|CardUpdatedEvent|CardDeletedEvent> */
class CardListener implements IEventListener {
	public function __construct(
		private ActivityBackend $activityBackend,
		private LoggerInterface $logger,
	) {
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
