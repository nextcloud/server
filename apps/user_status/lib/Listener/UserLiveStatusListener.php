<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Listener;

use OCA\DAV\CalDAV\Status\StatusService as CalendarStatusService;
use OCA\UserStatus\Connector\UserStatus as ConnectorUserStatus;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserLiveStatusEvent;
use OCP\UserStatus\IUserStatus;
use Psr\Log\LoggerInterface;

/**
 * Class UserDeletedListener
 *
 * @package OCA\UserStatus\Listener
 * @template-implements IEventListener<UserLiveStatusEvent>
 */
class UserLiveStatusListener implements IEventListener {
	public function __construct(
		private UserStatusMapper $mapper,
		private StatusService $statusService,
		private ITimeFactory $timeFactory,
		private CalendarStatusService $calendarStatusService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserLiveStatusEvent)) {
			// Unrelated
			return;
		}

		$user = $event->getUser();
		try {
			$this->calendarStatusService->processCalendarStatus($user->getUID());
			$userStatus = $this->statusService->findByUserId($user->getUID());
		} catch (DoesNotExistException $ex) {
			$userStatus = new UserStatus();
			$userStatus->setUserId($user->getUID());
			$userStatus->setStatus(IUserStatus::OFFLINE);
			$userStatus->setStatusTimestamp(0);
			$userStatus->setIsUserDefined(false);
		}

		// If the status is user-defined and one of the persistent status, we
		// will not override it.
		if ($userStatus->getIsUserDefined()
			&& \in_array($userStatus->getStatus(), StatusService::PERSISTENT_STATUSES, true)) {
			return;
		}

		// Don't overwrite the "away" calendar status if it's set
		if ($userStatus->getMessageId() === IUserStatus::MESSAGE_CALENDAR_BUSY) {
			$event->setUserStatus(new ConnectorUserStatus($userStatus));
			return;
		}

		$needsUpdate = false;

		// If the current status is older than 5 minutes,
		// treat it as outdated and update
		if ($userStatus->getStatusTimestamp() < ($this->timeFactory->getTime() - StatusService::INVALIDATE_STATUS_THRESHOLD)) {
			$needsUpdate = true;
		}

		// If the emitted status is more important than the current status
		// treat it as outdated and update
		if (array_search($event->getStatus(), StatusService::PRIORITY_ORDERED_STATUSES) < array_search($userStatus->getStatus(), StatusService::PRIORITY_ORDERED_STATUSES)) {
			$needsUpdate = true;
		}

		if ($needsUpdate) {
			$userStatus->setStatus($event->getStatus());
			$userStatus->setStatusTimestamp($event->getTimestamp());
			$userStatus->setIsUserDefined(false);

			if ($userStatus->getId() === null) {
				try {
					$this->mapper->insert($userStatus);
				} catch (Exception $e) {
					if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						// A different process might have written another status
						// update to the DB while we're processing our stuff.
						// We can safely ignore it as we're only changing between AWAY and ONLINE
						// and not doing anything with the message or icon.
						$this->logger->debug('Unique constraint violation for live user status', ['exception' => $e]);
						return;
					}
					throw $e;
				}
			} else {
				$this->mapper->update($userStatus);
			}
		}

		$event->setUserStatus(new ConnectorUserStatus($userStatus));
	}
}
