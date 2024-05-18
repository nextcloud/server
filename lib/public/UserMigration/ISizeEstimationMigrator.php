<?php

declare(strict_types=1);

/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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

namespace OCP\UserMigration;

use OCP\IUser;

/**
 * @since 25.0.0
 */
interface ISizeEstimationMigrator {
	/**
	 * Returns an estimate of the exported data size in KiB.
	 * Should be fast, favor performance over accuracy.
	 *
	 * @since 25.0.0
	 * @since 27.0.0 return value may overflow from int to float
	 */
	public function getEstimatedExportSize(IUser $user): int|float;
}
