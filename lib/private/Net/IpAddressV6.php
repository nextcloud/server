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
use OC\Net\AbstractIpAddress;

class IpAddressV6 extends AbstractIpAddress {
	private $original = '';
	private $address = '';
	private $cidrBits = 0;

	/**
	 * Returns the length of the represented IP address format in bits.
	 *
	 * @return int
	 */
	public function getMaxBitlength(): int {
		return 128;
	}

	/**
	 * Returns the regular expression for recognizing CIDR notation.
	 *
	 * @return string
	 */
	protected function getCidrRegex(): string {
		return '/^([0-9a-fA-F:]+[0-9a-fA-F:]+)\/([0-9]{1,3})$/';
	}

	/**
	 * Returns whether given $other address is either
	 * - equal to this instance regarding its IP address   or
	 * - is contained in the IP address range represented by this instance
	 *
	 * @param IIpAddress $other
	 * @return bool
	 */
	protected function matchCidr(IIpAddress $other): bool {
		$thisaddrn = inet_pton($this->getNetPart());
		$otheraddrn = inet_pton($other->getNetPart());
		if ($thisaddrn === false || $otheraddrn === false) {
			// if we can't handle ipV6 addresses, simply compare strings:
			return $this->matchOriginal($other);
		}

		$netbits = $this->getNetmaskBits();
		$thisaddra = unpack('C*', $thisaddrn);
		$otheraddra = unpack('C*', $otheraddrn);

		for ($i = 1; $i <= ceil($netbits / 8); $i++) {
			$mask = ($i * 8 <= $netbits)
				? 0xff
				: 0xff ^ (0xff >> ($netbits % 8));

			$thisaddrb = $thisaddra[$i] & $mask;
			$otheraddrb = $otheraddra[$i] & $mask;

			if (($thisaddrb ^ $otheraddrb) !== 0) {
				return false;
			}
		}

		return true;
	}
}

