<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\RecipientTypes;

use OCA\Sharing\Model\AShareRecipientType;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;

class UserShareRecipientType extends AShareRecipientType {
	public function searchRecipients(string $query, int $limit, int $offset): array {
		return array_map(static fn (IUser $user) => $user->getUID(), Server::get(IUserManager::class)->searchDisplayName($query, $limit, $offset));
	}

	public function validateRecipient(IUser $creator, string $recipient): bool {
		return Server::get(IUserManager::class)->userExists($recipient);
	}

	/**
	 * @return list<string>
	 */
	public function getRecipientValues(IUser $currentUser): array {
		return [$currentUser->getUID()];
	}
}
