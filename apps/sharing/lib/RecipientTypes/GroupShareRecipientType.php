<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\RecipientTypes;

use OCA\Sharing\Model\AShareRecipientType;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Server;

class GroupShareRecipientType extends AShareRecipientType {
	public function searchRecipients(string $query, int $limit, int $offset): array {
		return array_map(static fn (IGroup $group) => $group->getGID(), Server::get(IGroupManager::class)->search($query, $limit, $offset));
	}

	public function validateRecipient(IUser $creator, string $recipient): bool {
		return Server::get(IGroupManager::class)->groupExists($recipient);
	}

	public function getRecipientValues(IUser $currentUser): array {
		return Server::get(IGroupManager::class)->getUserGroupIds($currentUser);
	}
}
