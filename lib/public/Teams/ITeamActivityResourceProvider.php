<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * An optional Team resource provider capability for Team Activity.
 *
 * @since 36.0.0
 */
interface ITeamActivityResourceProvider extends ITeamResourceProvider {
	/**
	 * Resolve Team resources into Activity objects visible to the current user.
	 *
	 * @param list<TeamResource> $resources Resources from this provider shared with the Team
	 * @param string $userId The user the activity scopes are resolved for
	 * @return list<TeamActivityScope>
	 * @since 36.0.0
	 */
	public function getActivityScopes(array $resources, string $userId): array;
}
