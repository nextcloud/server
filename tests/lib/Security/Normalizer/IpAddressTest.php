<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Normalizer;

use OC\Security\Normalizer\IpAddress;
use Test\TestCase;

class IpAddressTest extends TestCase {
	public function subnetDataProvider() {
		return [
			[
				'64.233.191.254',
				'64.233.191.254/32',
			],
			[
				'192.168.0.123',
				'192.168.0.123/32',
			],
			[
				'::ffff:192.168.0.123',
				'192.168.0.123/32',
			],
			[
				'0:0:0:0:0:ffff:192.168.0.123',
				'192.168.0.123/32',
			],
			[
				'0:0:0:0:0:ffff:c0a8:7b',
				'192.168.0.123/32',
			],
			[
				'2001:0db8:0000:0000:0000:8a2e:0370:7334',
				'2001:db8::/48',
			],
			[
				'2001:db8:3333:4444:5555:6666:7777:8888',
				'2001:db8:3333::/48',
			],
			[
				'::1234:5678',
				'::/48',
			],
			[
				'[::1]',
				'::/48',
			],
		];
	}

	/**
	 * @dataProvider subnetDataProvider
	 *
	 * @param string $input
	 * @param string $expected
	 */
	public function testGetSubnet($input, $expected): void {
		$this->assertSame($expected, (new IpAddress($input))->getSubnet());
	}

	public function testToString(): void {
		$this->assertSame('127.0.0.1', (string)(new IpAddress('127.0.0.1')));
	}
}
