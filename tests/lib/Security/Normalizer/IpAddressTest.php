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
				'2001:0db8:85a3:0000:0000:8a2e:0370:7334',
				'2001:db8:85a3::/64',
			],
			[
				'2001:db8:3333:4444:5555:6666:7777:8888',
				'2001:db8:3333:4444::/64',
			],
			[
				'::1234:5678',
				'::/64',
			],
			[
				'[::1]',
				'::/64',
			],
		];
	}

	/**
	 * @dataProvider subnetDataProvider
	 *
	 * @param string $input
	 * @param string $expected
	 */
	public function testGetSubnet($input, $expected) {
		$this->assertSame($expected, (new IpAddress($input))->getSubnet());
	}

	public function testToString() {
		$this->assertSame('127.0.0.1', (string)(new IpAddress('127.0.0.1')));
	}
}
