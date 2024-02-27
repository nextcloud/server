<?php
/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\Preview;

/**
 * Interface IMimeIconProvider
 *
 * @since 28.0.0
 */
interface IMimeIconProvider {
	/**
	 * Get the URL to the icon for the given mime type
	 * Used by the preview provider to show a mime icon
	 * if no preview is available.
	 * @since 28.0.0
	 */
	public function getMimeIconUrl(string $mime): string|null;
}
