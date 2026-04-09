<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Service;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserSession;
use OCP\UserStatus\IUserStatus;

class JSDataService implements \JsonSerializable {

	/**
	 * JSDataService constructor.
	 *
	 * @param IUserSession $userSession
	 * @param StatusService $statusService
	 */
	public function __construct(
		private IUserSession $userSession,
		private StatusService $statusService,
	) {
	}

	public function jsonSerialize(): array {
		$user = $this->userSession->getUser();

		if ($user === null) {
			return [];
		}

		try {
			$status = $this->statusService->findByUserId($user->getUID());
		} catch (DoesNotExistException $ex) {
			return [
				'userId' => $user->getUID(),
				'message' => null,
				'messageId' => null,
				'messageIsPredefined' => false,
				'icon' => null,
				'clearAt' => null,
				'status' => IUserStatus::OFFLINE,
				'statusIsUserDefined' => false,
			];
		}

		return [
			'userId' => $status->getUserId(),
			'message' => $status->getCustomMessage(),
			'messageId' => $status->getMessageId(),
			'messageIsPredefined' => $status->getMessageId() !== null,
			'icon' => $status->getCustomIcon(),
			'clearAt' => $status->getClearAt(),
			'status' => $status->getStatus(),
			'statusIsUserDefined' => $status->getIsUserDefined(),
		];
	}
}
