<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\UserStatus;

use OCP\UserStatus\IProvider;

/**
 * Interface ISettableProvider
 * @package OC\UserStatus
 */
interface ISettableProvider extends IProvider {
	/**
	 * Set a new status for the selected user.
	 *
	 * @param string $userId The user for which we want to update the status.
	 * @param string $messageId The new message id.
	 * @param string $status The new status.
	 * @param bool $createBackup If true, this will store the old status so that it is possible to revert it later (e.g. after a call).
	 * @param string|null $customMessage
	 */
	public function setUserStatus(string $userId, string $messageId, string $status, bool $createBackup, ?string $customMessage = null): void;

	/**
	 * Revert an automatically set user status. For example after leaving a call,
	 * change back to the previously set status. If the user has already updated
	 * their status, this method does nothing.
	 *
	 * @param string $userId The user for which we want to update the status.
	 * @param string $messageId The expected current messageId.
	 * @param string $status The expected current status.
	 */
	public function revertUserStatus(string $userId, string $messageId, string $status): void;

	/**
	 * Revert an automatically set user status. For example after leaving a call,
	 * change back to the previously set status. If the user has already updated
	 * their status, this method does nothing.
	 *
	 * @param string[] $userIds The users for which we want to update the status.
	 * @param string $messageId The expected current messageId.
	 * @param string $status The expected current status.
	 */
	public function revertMultipleUserStatus(array $userIds, string $messageId, string $status): void;
}
