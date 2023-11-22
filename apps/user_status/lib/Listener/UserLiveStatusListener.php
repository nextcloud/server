<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\UserStatus\Listener;

use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Connector\UserStatus as ConnectorUserStatus;
use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventListener;
use OCP\EventDispatcher\Event;
use OCP\User\Events\UserLiveStatusEvent;
use OCP\UserStatus\IUserStatus;

/**
 * Class UserDeletedListener
 *
 * @package OCA\UserStatus\Listener
 */
class UserLiveStatusListener implements IEventListener {
	private UserStatusMapper $mapper;
	private StatusService $statusService;
	private ITimeFactory $timeFactory;

	public function __construct(UserStatusMapper $mapper,
								StatusService $statusService,
								ITimeFactory $timeFactory) {
		$this->mapper = $mapper;
		$this->statusService = $statusService;
		$this->timeFactory = $timeFactory;
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
		if ($userStatus->getIsUserDefined() &&
			\in_array($userStatus->getStatus(), StatusService::PERSISTENT_STATUSES, true)) {
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
				$this->mapper->insert($userStatus);
			} else {
				$this->mapper->update($userStatus);
			}
		}

		$event->setUserStatus(new ConnectorUserStatus($userStatus));
	}
}
