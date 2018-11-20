<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018, Oliver Wegner (void1976@gmail.com)
 *
 * @author Oliver Wegner <void1976@gmail.com>
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

/**
 * Public interface of ownCloud for use by core and apps.
 * IIpAddress interface
 */

namespace OCP\Net;

/**
 * This interface provides functionalities of an IP address or range,
 * e.g. checking if an IP address is within an IP range.
 *
 * @since 16.0.0
 */
interface IIpAddress {

	/**
	 * Returns whether this instance represents an IP range.
	 *
	 * @return boolean true if this is an IP range, false if it's a single IP address
	 * @since 16.0.0
	 */
	public function isRange(): bool;

	/**
	 * Returns if $other is equal to or contained in the IP
	 * address(es) which this instance represents.
	 *
	 * @return boolean true if $other is part of (or equal to) $this in terms of 
	 *         IP range terms, false otherwise
	 * @since 16.0.0
	 */
	public function containsAddress(IIpAddress $other): bool;
}

