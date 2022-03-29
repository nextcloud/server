<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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

namespace OCA\Theming;

/**
 * Interface ITheme
 *
 * @since 25.0.0
 */
interface ITheme {

	/**
	 * Unique theme id
	 * @since 25.0.0
	 */
	public function getId(): string;

	/**
	 * Get the media query triggering this theme
	 * Optional, ignored if falsy
	 *
	 * @return string
	 * @since 25.0.0
	 */
	public function getMediaQuery(): string;

	/**
	 * Return the list of changed css variables
	 *
	 * @return array
	 * @since 25.0.0
	 */
	public function getCSSVariables(): array;
}
