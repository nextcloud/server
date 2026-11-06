<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * An available app command with optional input or confirmation dialogs.
 *
 * @since 36.0.0
 */
final class TeamAppAction {
	/**
	 * @param string|null $icon Emoji or SVG path data for a 24x24 view box, starting with an M/m move command.
	 *                          Full SVG markup and image URLs are not supported.
	 * @param TeamAppActionPrompt|TeamAppActionConfirmation|null $dialog The single dialog to display before executing the action, if any.
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly ?string $icon = null,
		public readonly TeamAppActionPrompt|TeamAppActionConfirmation|null $dialog = null,
	) {
	}

}
