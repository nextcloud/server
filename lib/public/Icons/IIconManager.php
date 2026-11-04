<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Icons;

/**
 * Access to the icons shipped with Nextcloud.
 *
 * Icons are identified by name, for example `close` or `star_filled`.
 *
 * @since 36.0.0
 */
interface IIconManager {
	/**
	 * Check if an icon with the given name exists.
	 *
	 * @param string $name The icon name
	 * @since 36.0.0
	 */
	public function iconExists(string $name): bool;

	/**
	 * Get the URL of a single icon.
	 *
	 * The URL can be used as image source, or with the `#i` fragment as target of an SVG `<use>` element.
	 *
	 * @param string $name The icon name
	 * @param bool $absolute Whether to return an absolute URL
	 * @return string|null The URL or null if the icon does not exist
	 * @since 36.0.0
	 */
	public function getIconUrl(string $name, bool $absolute = false): ?string;
}
