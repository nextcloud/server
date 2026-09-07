<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Implementable;

/**
 * Provides the exclusive folder belonging to a team.
 *
 * Register implementations through
 * {@see \OCP\AppFramework\Bootstrap\IRegistrationContext::registerTeamResourceProvider()}.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
#[Implementable(since: '35.0.0')]
interface ITeamFolderProvider extends ITeamResourceProvider {

	/**
	 * Return the list of team folders that can be linked to the team.
	 *
	 * @since 35.0.0
	 */
	public function getLinkableTeamFolders(string $circleId): array;

	/**
	 * Link a team folder to the team.
	 *
	 * @since 35.0.0
	 */
	public function linkTeamFolder(string $circleId, int $folderId): TeamFolder;

	/**
	 * Return the folder exclusively linked to the team.
	 *
	 * @since 35.0.0
	 */
	public function getTeamFolder(string $teamId): ?TeamFolder;

	/**
	 * Create the exclusive folder for a team, or return its existing folder.
	 *
	 * @param Team $team The team that owns the folder.
	 * @param int $quota Quota in bytes; zero means unlimited.
	 * @since 35.0.0
	 */
	public function createTeamFolder(Team $team, int $quota = 0): TeamFolder;

	/**
	 * Update the storage quota of the team folder.
	 *
	 * @param string $teamId The team single id.
	 * @param int $quota Quota in bytes; zero means unlimited.
	 * @return TeamFolder The updated folder.
	 * @since 35.0.0
	 */
	public function updateTeamFolderQuota(string $teamId, int $quota): TeamFolder;

	/**
	 * Remove the exclusive relationship but retain the folder and its contents.
	 *
	 * @return TeamFolder|null The unlinked folder, if one existed.
	 * @since 35.0.0
	 */
	public function unlinkTeamFolder(string $teamId): ?TeamFolder;

	/**
	 * Remove the team folder and its contents.
	 *
	 * @since 35.0.0
	 */
	public function removeTeamFolder(string $teamId): bool;
}
