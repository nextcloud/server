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

class IpAddressV4 extends AbstractIpAddress {
	private $original = '';
	private $address = '';
	private $cidrBits = 0;

	public function getMaxBitlength(): int {
		return 32;
	}

	protected function getCidrRegex(): string {
		return '/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\/([0-9]{1,2})$/';
	}

	protected function matchCidr(IIpAddress $other): bool {
		$shiftbits = $this->getMaxBitlength() - $this->getNetmaskBits();
		$thisnum = ip2long($this->getNetPart()) >> $shiftbits;
		$othernum = ip2long($other->getNetPart()) >> $shiftbits;

		return $othernum === $thisnum;
	}
}

