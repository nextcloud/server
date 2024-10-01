<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Dashboard;

/**
 * Allow {@see IAPIWidgetV2} to reload their items
 *
 * @since 27.1.0
 */
interface IReloadableWidget extends IAPIWidgetV2 {
	/**
	 * Periodic interval in seconds in which to reload the widget's items
	 *
	 * @since 27.1.0
	 */
	public function getReloadInterval(): int;
}
