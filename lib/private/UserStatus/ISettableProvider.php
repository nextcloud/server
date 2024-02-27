<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
