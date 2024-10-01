<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Dashboard;

use OCP\Dashboard\Model\WidgetItems;

/**
 * Interface IAPIWidgetV2
 *
 * @since 27.1.0
 */
interface IAPIWidgetV2 extends IWidget {
	/**
	 * Items to render in the widget
	 *
	 * @since 27.1.0
	 */
	public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems;
}
