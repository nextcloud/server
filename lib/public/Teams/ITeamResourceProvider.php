<?php
/**
 * @copyright Copyright (c) 2024 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 */

namespace OCP\Teams;

/**
 * Implement a provider of resources that are shared or owned by a team
 *
 * @since 29.0.0
 */
interface ITeamResourceProvider {

	/**
	 * Unique identifier used to identify the provider (app id)
	 *
	 * @since 29.0.0
	 */
	public function getId(): string;

	/**
	 * User visible name of the provider (app name)
	 *
	 * @since 29.0.0
	 */
	public function getName(): string;

	/**
	 * Svg icon to show next to the provider (app icon)
	 *
	 * @since 29.0.0
	 */
	public function getIconSvg(): string;

	/**
	 * Return all resources that are shared to the given team id for the current provider
	 *
	 * @param string $teamId
	 * @return TeamResource[]
	 * @since 29.0.0
	 */
	public function getSharedWith(string $teamId): array;

	/**
	 * Check if a resource is shared with the given team
	 *
	 * @since 29.0.0
	 */
	public function isSharedWithTeam(string $teamId, string $resourceId): bool;

	/**
	 * Return team ids that a resource is shared with or owned by
	 *
	 * @return string[]
	 * @since 29.0.0
	 */
	public function getTeamsForResource(string $resourceId): array;
}
