<?php
declare(strict_types=1);

namespace OC\Net;

use OC\Net\IIpAddress;
use OC\Net\AbstractIpAddress;

class IpAddressV4 extends AbstractIpAddress {
	private $original = '';
	private $address = '';
	private $cidrBits = 0;

	public function __construct(string $address) {
		parent::__construct($address);
	}

	public function getMaxBitlength(): int {
		return 32;
	}

	protected function getCidrRegex(): string {
		return '/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\/([0-9]{1,2})$/';
	}

	protected function setOriginal(string $original) {
		$this->original = $original;
	}

	public function getOriginal(): string {
		return $this->original;
	}

	protected function setAddress(string $address) {
		$this->address = $address;
	}

	public function getAddress(): string {
		return $this->address;
	}

	protected function setNetmaskBits(int $bits) {
		$this->cidrBits = $bits;
	}

	public function getNetmaskBits(): int {
		return $this->cidrBits;
	}

	protected function matchCidr(IIpAddress $other): bool {
		$shiftbits = $this->getMaxBitlength() - $this->getNetmaskBits();
		$thisnum = ip2long($this->getAddress()) >> $shiftbits;
		$othernum = ip2long($other->getAddress()) >> $shiftbits;

		return $othernum === $thisnum;
	}
}

