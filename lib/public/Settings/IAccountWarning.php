<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Settings;

/**
 * @since 28.0.0
 */
interface IAccountWarning {
	public const SEVERITY_INFO = 'info';
	public const SEVERITY_WARNING = 'warning';
	public const SEVERITY_ERROR = 'error';

	/**
	 * Text to show to the admin
	 * @since 28.0.0
	 */
	public function getText(): string;

	/**
	 * Severity, one the SEVERITY_* constants
	 * @return self::SEVERITY_INFO|self::SEVERITY_WARNING|self::SEVERITY_ERROR
	 * @since 28.0.0
	 */
	public function getSeverity(): string;
}
