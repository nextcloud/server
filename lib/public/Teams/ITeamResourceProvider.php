<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
