<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Dashboard;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Attribute\Implementable;
use OCP\Dashboard\Model\WidgetItems;

/**
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
#[Implementable(since: '36.0.0')]
interface ITeamWidget extends IAPIWidgetV2 {
	/**
	 * Whether the widget has anything to show for this team; must not fetch items.
	 *
	 * @since 36.0.0
	 */
	public function isEnabledForTeam(string $teamId, string $userId): bool;

	/**
	 * Team-specific destination, or null to fall back to getUrl().
	 *
	 * @since 36.0.0
	 */
	public function getTeamUrl(string $teamId, string $userId): ?string;

	/**
	 * Implementations must enforce the user's access to the team's resources.
	 *
	 * @since 36.0.0
	 */
	public function getTeamItems(string $teamId, string $userId, ?string $since = null, int $limit = 7): WidgetItems;
}
