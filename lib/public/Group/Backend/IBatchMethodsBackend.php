<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Carl Schwan <carl@carlschwan.eu>
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
namespace OCP\Group\Backend;

/**
 * @brief Optional interface for group backends
 * @since 28.0.0
 */
interface IBatchMethodsBackend {
	/**
	 * @brief Batch method to check if a list of groups exists
	 *
	 * The default implementation in ABackend will just call groupExists in
	 * a loop. But a GroupBackend implementation should provides a more optimized
	 * override this method to provide a more optimized way to execute this operation.
	 *
	 * @param list<string> $gids
	 * @return list<string> the list of group that exists
	 * @since 28.0.0
	 */
	public function groupsExists(array $gids): array;

	/**
	 * @brief Batch method to get the group details of a list of groups
	 *
	 * The default implementation in ABackend will just call getGroupDetails in
	 * a loop. But a GroupBackend implementation should override this method
	 * to provide a more optimized way to execute this operation.
	 *
	 * @throw \RuntimeException if called on a backend that doesn't implements IGroupDetailsBackend
	 *
	 * @return array<string, array{displayName?: string}>
	 * @since 28.0.0
	 */
	public function getGroupsDetails(array $gids): array;
}
