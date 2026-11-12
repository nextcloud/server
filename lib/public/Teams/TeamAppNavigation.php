<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * Navigation entries and an optional create action for a team.
 *
 * @since 36.0.0
 */
final class TeamAppNavigation {
	/**
	 * @param list<TeamAppNavigationEntry> $entries App-owned navigation entries for the team.
	 * @param TeamAppNavigationAction|null $createAction The action to create a navigation entry.
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly array $entries,
		public readonly ?TeamAppNavigationAction $createAction = null,
	) {
	}

}
