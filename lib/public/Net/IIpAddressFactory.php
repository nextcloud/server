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
 * IIpAddressFactory interface
 */

namespace OCP\Net;

/**
 * This interface provides functionalities to create instances
 * of IIpAddress
 *
 * @since 16.0.0
 */
interface IIpAddressFactory {

	/**
	 * Returns a new instance of IIpAddress. The concrete implementation
	 * chosen depends on the kind of address (or subnet) that $address
	 * represents, e.g.
	 * - "192.168.0.2" for an Ipv4 address
	 * - "::1" for an Ipv6 address
	 * - "192.168.1.0/24" for an Ipv4 subnet
	 * - "2001:db8:85a3:8d3:1319:8a2e::/96" for an Ipv6 subnet
	 *
	 * @param string $address
	 * @return instance of IIpAddress
	 * @since 16.0.0
	 */
	public function getInstance(string $address): IIpAddress;
}

