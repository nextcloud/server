<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
 *
 * @author Benjamin Gaussorgues <benjamin.gaussorgues@nextcloud.com>
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
namespace OCP\Group\Backend;

/**
 * @since 30.0.0
 */
interface ICreateNamedGroupBackend {
	/**
	 * Tries to create a group from its name.
	 *
	 * If group name already exists, null is returned.
	 * Otherwise, new group ID is returned.
	 *
	 * @param string $name Group name
	 * @return ?string Group ID in case of success, null in case of failure
	 * @since 30.0.0
	 */
	public function createGroup(string $name): ?string;
}
