<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
