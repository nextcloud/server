<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Dashboard;

use OCP\Dashboard\Model\WidgetOptions;

/**
 * Allow getting widget options
 *
 * @since 25.0.0
 */
interface IOptionWidget extends IWidget {
	/**
	 * Get additional options for the widget
	 * @since 25.0.0
	 */
	public function getWidgetOptions(): WidgetOptions;
}
