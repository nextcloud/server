<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Dashboard;

use OCP\Dashboard\Model\WidgetItem;

/**
 * interface IAPIWidget
 *
 * @since 22.0.0
 */
interface IAPIWidget extends IWidget {
	/**
	 * @return list<WidgetItem> The widget items
	 * @since 22.0.0
	 */
	public function getItems(string $userId, ?string $since = null, int $limit = 7): array;
}
