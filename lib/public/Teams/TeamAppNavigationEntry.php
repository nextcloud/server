<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

/**
 * A navigation entry linking to a resource or an embedded app view.
 *
 * @since 36.0.0
 */
final class TeamAppNavigationEntry {
	/**
	 * @param string $id App-local navigation entry ID.
	 * @param string $label Display name of the navigation entry.
	 * @param string|null $icon Emoji or SVG path data for a 24x24 view box, starting with an M/m move command.
	 *                          Full SVG markup and image URLs are not supported.
	 * @param string|null $href URL to open the resource outside the embedded view.
	 * @param string|null $referenceUrl Canonical URL resolved by the Smart Picker for an embedded preview.
	 * @param 'link'|'reference'|null $openMode Open the href directly or show the reference URL as an embedded preview; null preserves automatic behavior.
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly ?string $icon = null,
		public readonly ?string $href = null,
		public readonly ?string $referenceUrl = null,
		public readonly ?string $openMode = null,
	) {
	}

}
