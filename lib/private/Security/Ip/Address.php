<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Ip;

use InvalidArgumentException;
use IPLib\Address\AddressInterface;
use IPLib\Factory;
use IPLib\ParseStringFlag;
use OCP\Security\Ip\IAddress;
use OCP\Security\Ip\IRange;

/**
 * @since 30.0.0
 */
class Address implements IAddress {
	private readonly AddressInterface $ip;

	public function __construct(string $ip) {
		$ip = Factory::parseAddressString($ip, ParseStringFlag::MAY_INCLUDE_ZONEID);
		if ($ip === null) {
			throw new InvalidArgumentException('Given IP address can’t be parsed');
		}
		$this->ip = $ip;
	}

	public static function isValid(string $ip): bool {
		return Factory::parseAddressString($ip, ParseStringFlag::MAY_INCLUDE_ZONEID) !== null;
	}

	public function matches(IRange ... $ranges): bool {
		foreach ($ranges as $range) {
			if ($range->contains($this)) {
				return true;
			}
		}

		return false;
	}

	public function __toString(): string {
		return $this->ip->toString();
	}
}
