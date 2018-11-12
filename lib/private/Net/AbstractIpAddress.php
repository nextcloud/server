<?php
declare(strict_types=1);

namespace OC\Net;

use OC\Net\IIpAddress;

abstract class AbstractIpAddress implements IIpAddress {
	abstract public function getMaxBitlength(): int;
	abstract protected function getCidrRegex(): string;
	abstract protected function matchCidr(IIpAddress $other): bool;

	private $original = '';
	private $netPart = '';
	private $netmaskBits = 0;

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

	protected function setOriginal(string $original) {
		$this->original = $original;
	}

	protected function getOriginal(): string {
		return $this->original;
	}

	protected function setNetPart(string $netPart) {
		$this->netPart = $netPart;
	}

	protected function getNetPart(): string {
		return $this->netPart;
	}

	protected function setNetmaskBits(int $bits) {
		$this->netmaskBits = $bits;
	}

	protected function getNetmaskBits(): int {
		return $this->netmaskBits;
	}

	protected function matchOriginal(IIpAddress $other): bool {
		return $other->getOriginal() === $this->getOriginal();
	}

	public function isRange(): bool {
		return $this->getNetmaskBits() < $this->getMaxBitlength();
	}

	public function containsAddress(IIpAddress $other): bool {
		return $this->isRange()
			? $this->matchCidr($other)
			: $this->matchOriginal($other);
	}
}

