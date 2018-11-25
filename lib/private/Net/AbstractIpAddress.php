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

namespace OC\Net;

use OCP\Net\IIpAddress;

abstract class AbstractIpAddress implements IIpAddress {
	abstract public function getMaxBitlength(): int;
	abstract protected function getCidrRegex(): string;
	abstract protected function matchCidr(IIpAddress $other): bool;

	protected $original = '';
	protected $netPart = '';
	protected $netmaskBits = 0;

	/**
	 * Constructor that takes an IP address in string form and
	 * initializes this instance to represent that address
	 *
	 * @param string $address
	 */
	public function __construct(string $address) {
		$this->setOriginal($address);

		if (preg_match($this->getCidrRegex(), $address, $match)) {
			$this->setNetPart($match[1]);
			$this->setNetmaskBits(max(0, min($this->getMaxBitlength(), intval($match[2]))));
		} else {
			$this->setNetPart($address);
			$this->setNetmaskbits($this->getMaxBitlength());
		}
	}

	/**
	 * Sets the literal address string that this instance
	 * represents
	 *
	 * @param string $original
	 */
	protected function setOriginal(string $original) {
		$this->original = $original;
	}

	/**
	 * Returns the literal address string that this instance
	 * represents
	 *
	 * @return string
	 */
	protected function getOriginal(): string {
		return $this->original;
	}

	/**
	 * Sets the network part of the
	 * address/range represented by this instance
	 *
	 * @param string $netPart
	 */
	protected function setNetPart(string $netPart) {
		$this->netPart = $netPart;
	}

	/**
	 * Returns the network part of the
	 * address/range represented by this instance
	 *
	 * @return string
	 */
	protected function getNetPart(): string {
		return $this->netPart;
	}

	/**
	 * Sets the number of bits of the net part of the IP
	 * address/range represented by this instance
	 *
	 * @param int $bits
	 */
	protected function setNetmaskBits(int $bits) {
		$this->netmaskBits = $bits;
	}

	/**
	 * Returns the number of bits of the net part of the IP
	 * address/range represented by this instance
	 *
	 * @return int
	 */
	protected function getNetmaskBits(): int {
		return $this->netmaskBits;
	}

	/**
	 * Returns whether $other is literally equivalent to this instance
	 *
	 * @return bool
	 */
	protected function matchOriginal(IIpAddress $other): bool {
		return $other->getOriginal() === $this->getOriginal();
	}

	/**
	 * Returns whether this instance represents an IP range (vs.
	 * a single IP address)
	 *
	 * @return bool
	 */
	public function isRange(): bool {
		return $this->getNetmaskBits() < $this->getMaxBitlength();
	}

	/**
	 * Returns whether given $other address is either
	 * - equal to this instance regarding its IP address   or
	 * - is contained in the IP address range represented by this instance
	 *
	 * @param IIpAddress $other
	 * @return bool
	 */
	public function containsAddress(IIpAddress $other): bool {
		return $this->isRange()
			? $this->matchCidr($other)
			: $this->matchOriginal($other);
	}
}

