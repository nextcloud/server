<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Dashboard;

use OCP\Dashboard\Model\WidgetItems;

/**
 * Provides team-scoped dashboard items visible to the requesting user.
 *
 * @since 36.0.0
 */
interface ITeamWidget extends IWidget {
	/**
	 * Return null when not applicable to this team, or an empty WidgetItems
	 * when applicable but empty. Implementations must enforce resource access.
	 *
	 * @since 36.0.0
	 */
	public function getTeamItems(string $teamId, string $userId, ?string $since = null, int $limit = 7): ?WidgetItems;
}
