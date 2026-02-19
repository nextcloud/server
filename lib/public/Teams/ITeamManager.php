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

	/**
	 * @param string[] $teams
	 * @return array<string, list<TeamResource>>
	 *
	 * @since 33.0.0
	 */
	public function getSharedWithList(array $teams, string $userId): array;

	/**
	 * Returns all teams that a given user is a member of
	 *
	 * @return list<Team>
	 * @since 33.0.0
	 */
	public function getTeamsForUser(string $userId): array;

	/**
	 * Returns a mapping of user id to display name for all members of a given team.
	 *
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
