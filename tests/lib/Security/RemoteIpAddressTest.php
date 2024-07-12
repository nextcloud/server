<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security;

use OC\Security\RemoteIpAddress;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class RemoteIpAddressTest extends \Test\TestCase {
	private IConfig $config;
	private IRequest $request;
	private LoggerInterface $logger;

	private RemoteIpAddress $remoteIpAddress;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->remoteIpAddress = new RemoteIpAddress($this->config, $this->request, $this->logger);
	}

	/**
	 * @param mixed $allowedRanges
	 * @dataProvider dataProvider
	 */
	public function testEmptyConfig(string $remoteIp, $allowedRanges, bool $expected): void {
		$this->request
			->method('getRemoteAddress')
			->willReturn($remoteIp);
		$this->config
		   ->method('getSystemValue')
		   ->with('allowed_admin_ranges', false)
		   ->willReturn($allowedRanges);

		$this->assertEquals($expected, $this->remoteIpAddress->allowsAdminActions());
	}

	/**
	 * @return array<string, mixed, bool>
	 */
	public function dataProvider(): array {
		return [
			// No configuration
			['1.2.3.4', false, true],
			['1234:4567:8910::', false, true],
			// Empty configuration
			['1.2.3.4', [], true],
			['1234:4567:8910::', [], true],
			// Invalid configuration
			['1.2.3.4', 'hello', true],
			['1234:4567:8910::', 'world', true],
			// Allowed IP
			['1.2.3.4', ['1.2.3.*'], true],
			['fc00:1:2:3::1', ['fc00::/7'], true],
			['1.2.3.4', ['192.168.1.2/24', '1.2.3.0/24'], true],
			['1234:4567:8910::1', ['fe80::/8','1234:4567::/16'], true],
			// Blocked IP
			['192.168.1.5', ['1.2.3.*'], false],
			['9234:4567:8910::', ['1234:4567:*'], false],
			['192.168.2.1', ['192.168.1.2/24', '1.2.3.0/24'], false],
			['9234:4567:8910::', ['fe80/8','1234:4567/16'], false],
		];
	}
}
