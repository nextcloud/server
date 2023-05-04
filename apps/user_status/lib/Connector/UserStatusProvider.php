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
namespace OCA\UserStatus\Connector;

use OCA\UserStatus\Service\StatusService;
use OCP\UserStatus\IProvider;
use OC\UserStatus\ISettableProvider;

class UserStatusProvider implements IProvider, ISettableProvider {

	/** @var StatusService */
	private $service;

	/**
	 * UserStatusProvider constructor.
	 *
	 * @param StatusService $service
	 */
	public function __construct(StatusService $service) {
		$this->service = $service;
	}

	/**
	 * @inheritDoc
	 */
	public function getUserStatuses(array $userIds): array {
		$statuses = $this->service->findByUserIds($userIds);

		$userStatuses = [];
		foreach ($statuses as $status) {
			$userStatuses[$status->getUserId()] = new UserStatus($status);
		}

		return $userStatuses;
	}

	public function setUserStatus(string $userId, string $messageId, string $status, bool $createBackup): void {
		$this->service->setUserStatus($userId, $status, $messageId, $createBackup);
	}

	public function revertUserStatus(string $userId, string $messageId, string $status): void {
		$this->service->revertUserStatus($userId, $messageId);
	}

	public function revertMultipleUserStatus(array $userIds, string $messageId, string $status): void {
		$this->service->revertMultipleUserStatus($userIds, $messageId);
	}
}
