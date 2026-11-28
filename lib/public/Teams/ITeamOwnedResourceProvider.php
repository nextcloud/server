<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

use OCP\AppFramework\Attribute\Implementable;

/**
 * A team resource provider with app-owned navigation and optional embedded
 * views on the Teams page.
 *
 * Register the implementation with IRegistrationContext::registerTeamResourceProvider().
 *
 * @since 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface ITeamOwnedResourceProvider extends ITeamResourceProvider {
	/**
	 * Return the app's team-owned navigation resources for this team. A resource's URL
	 * opens the app; its optional reference URL is used for embedded previews.
	 * When no reference URL is provided, the navigation URL is resolved instead.
	 *
	 * @param string $teamId
	 * @param string $userId
	 * @param bool $isAdmin
	 * @param TeamFolder|null $folder
	 * @return list<TeamResource>
	 * @since 36.0.0
	 */
	public function getOwnedByTeam(string $teamId, string $userId, bool $isAdmin, ?TeamFolder $folder): array;

	/**
	 * Check whether this provider can create a resource for the current team and user.
	 *
	 * @since 36.0.0
	 */
	public function canCreateResource(string $teamId, string $userId, bool $isAdmin, ?TeamFolder $folder): bool;

	/**
	 * Create a team-owned resource and return its navigation entry ID.
	 *
	 * @param string $teamId
	 * @param string $teamName
	 * @param string $userId
	 * @param bool $isAdmin
	 * @param TeamFolder|null $folder
	 * @return string The ID of the new navigation entry.
	 * @since 36.0.0
	 */
	public function createResource(string $teamId, string $teamName, string $userId, bool $isAdmin, ?TeamFolder $folder): string;
}
