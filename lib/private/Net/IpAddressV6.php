<?php
declare(strict_types=1);

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

