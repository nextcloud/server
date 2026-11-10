<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * App-owned navigation and optional embedded views on the Teams page.
 *
 * Register the implementation with IRegistrationContext::registerTeamAppProvider().
 *
 * @since 36.0.0
 */
interface ITeamAppProvider {
	/**
	 * Return the owning app ID.
	 *
	 * @since 36.0.0
	 */
	public function getId(): string;

	/**
	 * Return the default display order for this app's navigation entries and actions.
	 * Lower values appear first; ties are resolved by app ID.
	 * Saved team tab ordering takes precedence for navigation entries.
	 *
	 * @since 36.0.0
	 */
	public function getOrder(): int;

	/**
	 * Return navigation entries and an optional create action for this team.
	 *
	 * @param string $teamId
	 * @param string $userId
	 * @param bool $isAdmin
	 * @param TeamFolder|null $folder
	 * @return TeamAppNavigation Entries and an optional create action for this team.
	 * @since 36.0.0
	 */
	public function getNavigation(string $teamId, string $userId, bool $isAdmin, ?TeamFolder $folder): TeamAppNavigation;

	/**
	 * Add an app-owned resource to the team and return its navigation entry ID.
	 *
	 * @param string $teamId
	 * @param string $teamName
	 * @param string $userId
	 * @param bool $isAdmin
	 * @param TeamFolder|null $folder
	 * @param string|null $value The prompt value, or null when the action has no prompt.
	 * @return string The ID of the new navigation entry.
	 * @since 36.0.0
	 */
	public function addResource(string $teamId, string $teamName, string $userId, bool $isAdmin, ?TeamFolder $folder, ?string $value): string;
}
