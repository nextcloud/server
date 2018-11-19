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

class IpAddressV4 extends AbstractIpAddress {
	private $original = '';
	private $address = '';
	private $cidrBits = 0;

	/**
	 * Returns the length of the represented IP address format in bits.
	 *
	 * @return int
	 */
	public function getMaxBitlength(): int {
		return 32;
	}

	/**
	 * Returns the regular expression for recognizing CIDR notation.
	 *
	 * @return string
	 */
	protected function getCidrRegex(): string {
		return '/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\/([0-9]{1,2})$/';
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
		$shiftbits = $this->getMaxBitlength() - $this->getNetmaskBits();
		$thisnum = ip2long($this->getNetPart()) >> $shiftbits;
		$othernum = ip2long($other->getNetPart()) >> $shiftbits;

		return $othernum === $thisnum;
	}
}

