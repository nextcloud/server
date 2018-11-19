<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018, Oliver Wegner (void1976@gmail.com)
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

namespace OC\Net;

use OC\Net\IIpAddress;
use OC\Net\IpAddressV4;
use OC\Net\IpAddressV6;

class IpAddressFactory {
	/**
	 * Returns whether $address represents an IPv6 address
	 *
	 * @param string $address
	 * @return bool
	 */
	public static function isIpv6(string $address): bool {
		return strpos($address, ':') !== false;
	}

	/**
	 * Creates a new instance conforming to IIpAddress and
	 * representing the given $address.
	 *
	 * @param string $address
	 * @return IIpAddress
	 */
	public static function new($address): IIpAddress {
		if (self::isIpv6($address)) {
			return new IpAddressV6($address);
		} else {
			return new IpAddressV4($address);
		}
	}
}

