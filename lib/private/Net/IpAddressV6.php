<?php
declare(strict_types=1);

namespace OC\Net;

use OC\Net\IIpAddress;
use OC\Net\AbstractIpAddress;

class IpAddressV6 extends AbstractIpAddress {
	private $original = '';
	private $address = '';
	private $cidrBits = 0;

	public function __construct(string $address) {
		parent::__construct($address);
	}

	public function getMaxBitlength(): int {
		return 128;
	}

	protected function getCidrRegex(): string {
		return '/^([0-9a-fA-F:]+[0-9a-fA-F:]+)\/([0-9]{1,3})$/';
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
		$thisaddrn = inet_pton($this->getAddress());
		$otheraddrn = inet_pton($other->getAddress());
		if ($thisaddrn === false || $otheraddrn === false) {
			// if we can't handle ipV6 addresses, simply compare strings:
			return $this->matchLiteral($other);
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

