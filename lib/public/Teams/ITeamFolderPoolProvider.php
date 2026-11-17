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
 * Optional storage allocation support for a team folder provider.
 * A pool finances folders; it does not grant access or group membership.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
#[Implementable(since: '36.0.0')]
interface ITeamFolderPoolProvider extends ITeamFolderProvider {
	/**
	 * Read configured group limits without calculating allocations.
	 * The default pool is omitted; absent groups have no configured pool.
	 *
	 * @return array<string, int|null>
	 * @since 36.0.0
	 */
	public function getStoragePoolLimits(): array;

	/**
	 * Read configured financing-team limits without calculating allocations.
	 *
	 * @return array<string, int|null> Team id to pool limit.
	 * @since 36.0.0
	 */
	public function getTeamStoragePoolLimits(): array;

	/**
	 * Return pools and allocated folder quotas without filesystem operations.
	 *
	 * @return list<TeamFolderStoragePool>
	 * @since 36.0.0
	 */
	public function getStoragePools(): array;

	/**
	 * Set a pool limit. Null group and team identify the default pool.
	 * Group and source team are mutually exclusive financing identities.
	 * Null means unlimited; zero means no budget. Existing allocations must fit.
	 *
	 * @throws TeamFolderPoolQuotaException
	 * @since 36.0.0
	 */
	public function setStoragePoolLimit(?string $groupId, ?int $limit, ?string $sourceTeamId = null): TeamFolderStoragePool;

	/**
	 * Delete a group or team pool and atomically move its assignments to the default pool.
	 * The default pool cannot be deleted. Folder quotas and permissions stay unchanged.
	 *
	 * @throws TeamFolderPoolQuotaException When the default pool cannot fund the folders.
	 * @since 36.0.0
	 */
	public function deleteStoragePool(?string $groupId, ?string $sourceTeamId = null): void;

	/**
	 * Assign or move a team's financing pool without changing folder permissions.
	 * The full existing folder quota must fit in the destination pool.
	 *
	 * @param bool $onlyIfUnassigned Preserve an existing assignment when true.
	 * @param ?string $sourceTeamId Financing team, mutually exclusive with groupId.
	 * @throws TeamFolderPoolQuotaException
	 * @since 36.0.0
	 */
	public function assignTeamStoragePool(string $teamId, ?string $groupId, bool $onlyIfUnassigned = false, ?string $sourceTeamId = null): void;

	/**
	 * Read stable team-to-pool assignments. Unassigned teams are omitted.
	 *
	 * @param list<string> $teamIds
	 * @return array<string, string> Team id to pool id.
	 * @since 36.0.0
	 */
	public function getTeamStoragePoolAssignments(array $teamIds): array;

	/**
	 * Read folder metadata in bulk without filesystem operations.
	 *
	 * @param list<string> $teamIds
	 * @return array<string, TeamFolder> Teams without a folder are omitted.
	 * @since 36.0.0
	 */
	public function getTeamFolderSummaries(array $teamIds): array;

	/**
	 * Read cached file sizes in bulk. Null denotes unknown usage.
	 *
	 * @param list<string> $teamIds
	 * @return array<string, int|null> Team id to used bytes.
	 * @since 36.0.0
	 */
	public function getTeamFolderUsage(array $teamIds): array;
}