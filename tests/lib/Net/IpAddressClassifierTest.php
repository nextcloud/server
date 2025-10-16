<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Net;

use OC\Net\IpAddressClassifier;
use Test\TestCase;

class IpAddressClassifierTest extends TestCase {
	private IpAddressClassifier $classifier;

	protected function setUp(): void {
		parent::setUp();

		$this->classifier = new IpAddressClassifier();
	}

	public static function publicIpAddressData(): array {
		return [
			['8.8.8.8'],
			['8.8.4.4'],
			['2001:4860:4860::8888'],
			['2001:4860:4860::8844'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('publicIpAddressData')]
	public function testPublicAddress(string $ip): void {
		$isLocal = $this->classifier->isLocalAddress($ip);

		self::assertFalse($isLocal);
	}

	public static function localIpAddressData(): array {
		return [
			['127.0.0.1'],
			['127.0.0.13'], // all 127.0.0.0/8 network is loopback address
			['192.168.0.1'],
			['fe80::200:5aee:feaa:20a2'],
			['fe80::1fc4:15d8:78db:2319%enp4s0'], // v6 zone ID
			['0:0:0:0:0:ffff:10.0.0.1'],
			['0:0:0:0:0:ffff:127.0.0.0'],
			['10.0.0.1'],
			['::'],
			['::1'],
			['100.100.100.200'],
			['192.0.0.1'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('localIpAddressData')]
	public function testLocalAddress(string $ip): void {
		$isLocal = $this->classifier->isLocalAddress($ip);

		self::assertTrue($isLocal);
	}
}
