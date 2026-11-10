<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * An available app command with an optional text prompt.
 *
 * @since 36.0.0
 */
final class TeamAppNavigationAction {
	/**
	 * @param string|null $icon Emoji or SVG path data for a 24x24 view box, starting with an M/m move command.
	 *                          Full SVG markup and image URLs are not supported.
	 * @param string|null $prompt The input label to show before executing the action, if any.
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly ?string $icon = null,
		public readonly ?string $prompt = null,
	) {
	}

}
