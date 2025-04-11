<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Security\Ip;

use InvalidArgumentException;
use IPLib\Factory;
use IPLib\ParseStringFlag;
use IPLib\Range\RangeInterface;
use OCP\Security\Ip\IAddress;
use OCP\Security\Ip\IRange;

class Range implements IRange {
	private readonly RangeInterface $range;

	public function __construct(string $range) {
		$range = Factory::parseRangeString($range);
		if ($range === null) {
			throw new InvalidArgumentException('Given range can’t be parsed');
		}
		$this->range = $range;
	}

	public static function isValid(string $range): bool {
		return Factory::parseRangeString($range) !== null;
	}

	public function contains(IAddress $address): bool {
		return $this->range->contains(Factory::parseAddressString((string)$address, ParseStringFlag::MAY_INCLUDE_ZONEID));
	}

	public function __toString(): string {
		return $this->range->toString();
	}
}
