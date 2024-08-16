<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Dashboard;

/**
 * interface IConditionalWidget
 *
 * Allows an app to lazy-register a widget and in the lazy part of the code
 * it can decide if the widget should really be registered.
 *
 * @since 26.0.0
 */
interface IConditionalWidget extends IWidget {
	/**
	 * @return bool Whether the widget is enabled and should be registered
	 * @since 26.0.0
	 */
	public function isEnabled(): bool;
}
