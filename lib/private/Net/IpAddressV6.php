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

namespace OC\Net;

use OC\Net\IIpAddress;
use OC\Net\AbstractIpAddress;

class IpAddressV6 extends AbstractIpAddress {
	private $original = '';
	private $address = '';
	private $cidrBits = 0;

	public function getMaxBitlength(): int {
		return 128;
	}

	protected function getCidrRegex(): string {
		return '/^([0-9a-fA-F:]+[0-9a-fA-F:]+)\/([0-9]{1,3})$/';
	}

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

