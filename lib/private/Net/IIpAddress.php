<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018, ownCloud, Inc.
 *
 * @author Oliver Wegner <void1976@gmail.com>
 *
 * @license AGPL-3.0
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Private interface of ownCloud for internal use.
 * IpAddress interface
 */

/**
 * This interface provides functionalities of an IP address or range,
 * e.g. checking if an IP address is within an IP range.
 *
 * @since 15.0.0
 */

namespace OC\Net;

interface IIpAddress {

	/**
	 * Returns whether this instance represents an IP range.
	 *
	 * @return boolean true if this is an IP range, false if it's a single IP address
	 * @since 15.0.0
	 */
	public function isRange(): bool;

	/**
	 * Returns if $other is equal to or contained in the IP
	 * address(es) which this instance represents.
	 *
	 * @return boolean true if $other is part of (or equal to) $this in terms of 
	 *         IP range terms, false otherwise
	 * @since 15.0.0
	 */
	public function containsAddress(IIpAddress $other): bool;
}

