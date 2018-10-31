<?php
declare(strict_types=1);

namespace OC\Net;

use OC\Net\IIpAddress;

abstract class AbstractIpAddress implements IIpAddress {
	abstract public static function getMaxBitlength(): int;
	abstract protected static function getCidrRegex(): string;

	abstract protected function setOriginal(string $original);
	abstract public function getOriginal(): string;
	abstract protected function setAddress(string $address);
	abstract public function getAddress(): string;
	abstract protected function setNetmaskBits(int $bits);
	abstract public function getNetmaskBits(): int;
	abstract protected function matchCidr(IIpAddress $other): bool;

	public function __construct(string $address) {
		$this->setOriginal($address);

		if (preg_match(self::getCidrRegex(), $address, $match)) {
			return array($match[1], max(0, min(self::getMaxBitlength(), intval($match[2]))));
		} else {
			return array($address, self::getMaxBitlength());
		}
	}

	protected function matchLiteral(IIpAddress $other): bool {
		return $other->getOriginal() === $this->getOriginal();
	}

	public function isRange(): string {
		return $this->getNetmaskBits() < self::getMaxBitlength();
	}

	public function containsAddress(IIpAddress $other): bool {
		return $this->isRange()
			? $this->matchCidr($other)
			: $this->matchLiteral($ohter);
	}
}

