<?php

declare(strict_types = 1);
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
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

namespace OCP;

/**
 * Service that find the binary path for a program.
 *
 * This interface should be injected via depency injection and must
 * not be implemented in applications.
 *
 * @since 25.0.0
 */
interface IBinaryFinder {
	/**
	 * Try to find a program
	 *
	 * @return false|string
	 * @since 25.0.0
	 */
	public function findBinaryPath(string $program);
}
