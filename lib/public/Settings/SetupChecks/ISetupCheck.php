<?php

declare(strict_types=1);

/**
 * @copyright 2020 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Settings\SetupChecks;

/**
 * Interface to implement for setup checks.
 *
 * There is no way (yet) for apps to register setup checks.
 *
 * @since 21.0.0
 */
interface ISetupCheck {
	/**
	 * @since 21.0.0
	 */
	public const SEVERITY_INFO = 'info';

	/**
	 * @since 21.0.0
	 */
	public const SEVERITY_WARNING = 'warning';

	/**
	 * @since 21.0.0
	 */
	public const SEVERITY_ERROR = 'error';

	/**
	 * A description about the setup check
	 *
	 * @return string
	 * @since 21.0.0
	 */
	public function description(): string;

	/**
	 * Severity of setup check
	 *
	 * @psalm-return ISetupCheck::SEVERITY*
	 * @return string
	 * @since 21.0.0
	 */
	public function severity(): string;

	/**
	 * Execute the setup check. Make sure the setup check is idempotent.
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function passes(): bool;

	/**
	 * A absolute link to the documentation. An empty string if no documentation is available.
	 *
	 * @return string
	 * @since 21.0.0
	 */
	public function linkToDocumentation(): ?string;
}
