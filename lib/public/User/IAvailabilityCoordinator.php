<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User;

use OCP\IUser;

/**
 * Coordinator for availability and out-of-office messages
 *
 * @since 28.0.0
 */
interface IAvailabilityCoordinator {
	/**
	 * Check if the feature is enabled on this instance
	 *
	 * @return bool
	 *
	 * @since 28.0.0
	 */
	public function isEnabled(): bool;

	/**
	 * Get the user's out-of-office message, if any
	 *
	 * @since 28.0.0
	 */
	public function getCurrentOutOfOfficeData(IUser $user): ?IOutOfOfficeData;

	/**
	 * Reset the absence cache to null
	 *
	 * @since 28.0.0
	 */
	public function clearCache(string $userId): void;

	/**
	 * Is the absence in effect at this moment
	 *
	 * @param IOutOfOfficeData $data
	 * @return bool
	 * @since 28.0.0
	 */
	public function isInEffect(IOutOfOfficeData $data): bool;
}
