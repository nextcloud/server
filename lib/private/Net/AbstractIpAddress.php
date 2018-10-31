<?php
declare(strict_types=1);

namespace OC\Net;

use OC\Net\IIpAddress;

abstract class AbstractIpAddress implements IIpAddress {
	abstract public function getMaxBitlength(): int;
	abstract protected function getCidrRegex(): string;

	abstract protected function setOriginal(string $original);
	abstract public function getOriginal(): string;
	abstract protected function setAddress(string $address);
	abstract public function getAddress(): string;
	abstract protected function setNetmaskBits(int $bits);
	abstract public function getNetmaskBits(): int;
	abstract protected function matchCidr(IIpAddress $other): bool;

	public function __construct(string $address) {
		$this->setOriginal($address);

		if (preg_match($this->getCidrRegex(), $address, $match)) {
			$this->setAddress($match[1]);
			$this->setNetmaskBits(max(0, min($this->getMaxBitlength(), intval($match[2]))));
		} else {
			$this->setAddress($address);
			$this->setNetmaskbits($this->getMaxBitlength());
		}
	}

	protected function matchLiteral(IIpAddress $other): bool {
		return $other->getOriginal() === $this->getOriginal();
	}

	public function isRange(): bool {
		return $this->getNetmaskBits() < $this->getMaxBitlength();
	}

	public function containsAddress(IIpAddress $other): bool {
		return $this->isRange()
			? $this->matchCidr($other)
			: $this->matchLiteral($other);
	}
}

