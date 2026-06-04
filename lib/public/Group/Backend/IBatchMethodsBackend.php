<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * @throws \RuntimeException if called on a backend that doesn't implements IGroupDetailsBackend
	 *
	 * @return array<string, array{displayName?: string}>
	 * @since 28.0.0
	 */
	public function getGroupsDetails(array $gids): array;
}
