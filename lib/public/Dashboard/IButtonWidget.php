<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Dashboard;

use OCP\Dashboard\Model\WidgetButton;

/**
 * Adds a button to the dashboard api representation
 *
 * @since 25.0.0
 */
interface IButtonWidget extends IWidget {
	/**
	 * Get the buttons to show on the widget
	 *
	 * @param string $userId
	 * @return list<WidgetButton>
	 * @since 25.0.0
	 */
	public function getWidgetButtons(string $userId): array;
}
