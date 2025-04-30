<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Ip;

use OC\Security\Ip\RemoteAddress;
use OCP\IConfig;
use OCP\IRequest;

class RemoteAddressTest extends \Test\TestCase {
	private IConfig $config;
	private IRequest $request;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->request = $this->createMock(IRequest::class);
	}

	/**
	 * @param mixed $allowedRanges
	 * @dataProvider dataProvider
	 */
	public function testAllowedIps(string $remoteIp, $allowedRanges, bool $expected): void {
		$this->request
			->method('getRemoteAddress')
			->willReturn($remoteIp);
		$this->config
			->method('getSystemValue')
			->with('allowed_admin_ranges', false)
			->willReturn($allowedRanges);

		$remoteAddress = new RemoteAddress($this->config, $this->request);

		$this->assertEquals($expected, $remoteAddress->allowsAdminActions());
	}

	/**
	 * @return array<string, mixed, bool>
	 */
	public function dataProvider(): array {
		return [
			// No IP (ie. CLI)
			['', ['192.168.1.2/24'], true],
			['', ['fe80/8'], true],
			// No configuration
			['1.2.3.4', false, true],
			['1234:4567:8910::', false, true],
			// v6 Zone ID
			['fe80::1fc4:15d8:78db:2319%enp4s0', false, true],
			// Empty configuration
			['1.2.3.4', [], true],
			['1234:4567:8910::', [], true],
			// Invalid configuration
			['1.2.3.4', 'hello', true],
			['1234:4567:8910::', 'world', true],
			// Mixed configuration
			['192.168.1.5', ['1.2.3.*', '1234::/8'], false],
			['::1', ['127.0.0.1', '1234::/8'], false],
			['192.168.1.5', ['192.168.1.0/24', '1234::/8'], true],
			// Allowed IP
			['1.2.3.4', ['1.2.3.*'], true],
			['fc00:1:2:3::1', ['fc00::/7'], true],
			['1.2.3.4', ['192.168.1.2/24', '1.2.3.0/24'], true],
			['1234:4567:8910::1', ['fe80::/8','1234:4567::/16'], true],
			// Blocked IP
			['192.168.1.5', ['1.2.3.*'], false],
			['9234:4567:8910::', ['1234:4567::1'], false],
			['192.168.2.1', ['192.168.1.2/24', '1.2.3.0/24'], false],
			['9234:4567:8910::', ['fe80::/8','1234:4567::/16'], false],
		];
	}
}
