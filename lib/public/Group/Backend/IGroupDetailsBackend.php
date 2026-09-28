<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Group\Backend;

/**
 * @brief Optional interface for group backends
 * @since 14.0.0
 */
interface IGroupDetailsBackend {
	/**
	 * @brief Get additional details for a group, for example the display name.
	 *
	 * The array returned can be empty when no additional information is available
	 * for the group.
	 *
	 * @return array{displayName?: string}
	 * @since 14.0.0
	 */
	public function getGroupDetails(string $gid): array;
}
