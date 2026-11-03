<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Command;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Completion\CompletionInput;

/**
 * @since 35.0.0
 */
#[Consumable('35.0.0')]
class Completion {
	/**
	 * @since 35.0.0
	 */
	public static function completeGroupId(CompletionInput $input): array {
		$groupManager = \OCP\Server::get(IGroupManager::class);
		return array_map(function (IGroup $group) {
			return $group->getGID();
		}, $groupManager->search($input->getCompletionValue()));
	}

	/**
	 * @since 35.0.0
	 */
	public static function completeUserId(CompletionInput $input): array {
		$userManager = \OCP\Server::get(IUserManager::class);
		return array_map(function (IUser $user) {
			return $user->getUID();
		}, $userManager->searchDisplayName($input->getCompletionValue()));
	}
}
