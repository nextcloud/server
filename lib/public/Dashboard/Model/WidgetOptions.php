<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Dashboard\Model;

/**
 * Option for displaying a widget
 *
 * @since 25.0.0
 */
class WidgetOptions {
	private bool $roundItemIcons;

	/**
	 * @param bool $roundItemIcons
	 * @since 25.0.0
	 */
	public function __construct(bool $roundItemIcons) {
		$this->roundItemIcons = $roundItemIcons;
	}

	/**
	 * Get the default set of options
	 *
	 * @return WidgetOptions
	 * @since 25.0.0
	 */
	public static function getDefault(): WidgetOptions {
		return new WidgetOptions(false);
	}

	/**
	 * Whether the clients should render icons for widget items as round icons
	 *
	 * @return bool
	 * @since 25.0.0
	 */
	public function withRoundItemIcons(): bool {
		return $this->roundItemIcons;
	}
}
