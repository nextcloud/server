<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\UserStatus;

/**
 * This interface allows to manage the user status.
 *
 * This interface must not be implemented in your application but
 * instead should be used as a service and injected in your code with
 * dependency injection.
 *
 * @since 20.0.0
 */
interface IManager {
	/**
	 * Gets the statuses for all users in $users
	 *
	 * @param string[] $userIds
	 * @return array<string, IUserStatus> array key being the userid, users without a status will not be in the returned array
	 * @since 20.0.0
	 */
	public function getUserStatuses(array $userIds): array;


	/**
	 * Set a new status for the selected user.
	 *
	 * @param string $userId The user for which we want to update the status.
	 * @param string $messageId The id of the predefined message.
	 * @param string $status The status to assign
	 * @param bool $createBackup If true, this will store the old status so that it is possible to revert it later (e.g. after a call).
	 * @param string|null $customMessage
	 * @since 23.0.0
	 * @since 28.0.0 Optional parameter $customMessage was added
	 */
	public function setUserStatus(string $userId, string $messageId, string $status, bool $createBackup = false, ?string $customMessage = null): void;

	/**
	 * Revert an automatically set user status. For example after leaving a call,
	 * change back to the previously set status.
	 *
	 * @param string $userId The user for which we want to update the status.
	 * @param string $messageId The expected current messageId. If the user has already updated their status, this method does nothing.
	 * @param string $status The expected current status. If the user has already updated their status, this method does nothing.
	 * @since 23.0.0
	 */
	public function revertUserStatus(string $userId, string $messageId, string $status): void;

	/**
	 * Revert an automatically set user status. For example after leaving a call,
	 * change back to the previously set status.
	 *
	 * @param string[] $userIds The user for which we want to update the status.
	 * @param string $messageId The expected current messageId. If the user has already updated their status, this method does nothing.
	 * @param string $status The expected current status. If the user has already updated their status, this method does nothing.
	 * @since 23.0.0
	 */
	public function revertMultipleUserStatus(array $userIds, string $messageId, string $status): void;
}
