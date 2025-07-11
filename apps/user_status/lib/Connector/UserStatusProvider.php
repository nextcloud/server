<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Connector;

use OC\UserStatus\ISettableProvider;
use OCA\UserStatus\Service\StatusService;
use OCP\UserStatus\IProvider;

class UserStatusProvider implements IProvider, ISettableProvider {

	/**
	 * UserStatusProvider constructor.
	 *
	 * @param StatusService $service
	 */
	public function __construct(
		private StatusService $service,
	) {
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

	public function setUserStatus(string $userId, string $messageId, string $status, bool $createBackup, ?string $customMessage = null): void {
		$this->service->setUserStatus($userId, $status, $messageId, $createBackup, $customMessage);
	}

	public function revertUserStatus(string $userId, string $messageId, string $status): void {
		$this->service->revertUserStatus($userId, $messageId);
	}

	public function revertMultipleUserStatus(array $userIds, string $messageId, string $status): void {
		$this->service->revertMultipleUserStatus($userIds, $messageId);
	}
}
