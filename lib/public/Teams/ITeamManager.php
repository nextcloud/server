<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * @since 29.0.0
 */
interface ITeamManager {
	/**
	 * Get all providers that have registered as a team resource provider
	 *
	 * @return ITeamResourceProvider[]
	 * @since 29.0.0
	 */
	public function getProviders(): array;

	/**
	 * Get a specific team resource provider by its id
	 *
	 * @since 29.0.0
	 */
	public function getProvider(string $providerId): ITeamResourceProvider;

	/**
	 * Returns all team resources for a given team and user
	 *
	 * @return list<TeamResource>
	 * @since 29.0.0
	 */
	public function getSharedWith(string $teamId, string $userId): array;

	/**
	 * Returns all teams for a given resource and user
	 *
	 * @since 29.0.0
	 */
	public function getTeamsForResource(string $providerId, string $resourceId, string $userId): array;
}
