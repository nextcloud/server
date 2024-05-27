<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Dashboard;

/**
 * Allow getting the absolute icon url for a widget
 *
 * @since 25.0.0
 */
interface IIconWidget extends IWidget {
	/**
	 * Get the absolute url for the widget icon
	 *
	 * @return string
	 * @since 25.0.0
	 */
	public function getIconUrl(): string;
}
