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
	 * Get all providers that have registered as team resource providers.
	 *
	 * @return array<string, ITeamResourceProvider> Provider ID => provider
	 * @since 29.0.0
	 */
	public function getProviders(): array;

	/**
	 * Get a specific team resource provider by its ID.
	 *
	 * @param string $providerId Identifier of the provider
	 * @return ITeamResourceProvider
	 * @throws \RuntimeException If no provider exists for the given ID
	 * @since 29.0.0
	 */
	public function getProvider(string $providerId): ITeamResourceProvider;

	/**
	 * Returns all team resources for a given team and user.
	 *
	 * @param string $teamId ID of the team whose resources are being queried
	 * @param string $userId ID of the user from whose point of view the resources are being queried
	 * @return list<TeamResource>
	 * @since 29.0.0
	 */
	public function getSharedWith(string $teamId, string $userId): array;

	/**
	 * Returns all teams for a given resource and user.
	 *
	 * @param string $providerId Identifier of the provider (e.g. deck, talk, collectives)
	 * @param string $resourceId Unique ID of the resource to list teams for
	 * @param string $userId ID of the user from whose point of view the teams are being queried
	 * @return list<Team>
	 * @since 29.0.0
	 */
	public function getTeamsForResource(string $providerId, string $resourceId, string $userId): array;

	/**
	 * Returns team resources grouped by team ID for the given team IDs.
	 *
	 * @param list<string> $teams Team IDs
	 * @param string $userId User ID
	 * @param string $resourceId Unique ID of the resource to filter shares for, if supported by the provider
	 * @param list<string> $teams Team IDs
	 * @return array<string, list<TeamResource>>
	 * @since 33.0.0
	 * @since 34.0.0 Added $resourceId param
	 */
	public function getSharedWithList(array $teams, string $userId, string $resourceId): array;

	/**
	 * Returns all teams that a given user is a member of.
	 *
	 * @param string $userId ID of the user whose teams are being queried
	 * @return list<Team>
	 * @since 33.0.0
	 */
	public function getTeamsForUser(string $userId): array;

	/**
	 * Returns a mapping of user ID to display name for all members of a given team.
	 *
	 * Includes both direct and inherited members.
	 *
	 * @param string $teamId ID of the team whose members are being queried
	 * @param string $userId ID of the user from whose point of view the members are being queried
	 * @return array<string, string> userId => displayName
	 * @since 34.0.0
	 */
	public function getMembersOfTeam(string $teamId, string $userId): array;

	/**
	 * Returns whether the Teams backend is available
	 *
	 * @return bool
	 * @since 34.0.0
	 */
	public function hasTeamSupport(): bool;
}
