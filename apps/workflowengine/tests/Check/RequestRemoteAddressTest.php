<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\RequestRemoteAddress;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class RequestRemoteAddressTest extends \Test\TestCase {

	protected IRequest&MockObject $request;

	protected function getL10NMock(): IL10N&MockObject {
		$l = $this->createMock(IL10N::class);
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return sprintf($string, $args);
			});
		return $l;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
	}

	public static function dataExecuteCheckIPv4(): array {
		return [
			['127.0.0.1/32', '127.0.0.1', true],
			['127.0.0.1/32', '127.0.0.0', false],
			['127.0.0.1/31', '127.0.0.0', true],
			['127.0.0.1/32', '127.0.0.2', false],
			['127.0.0.1/31', '127.0.0.2', false],
			['127.0.0.1/30', '127.0.0.2', true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataExecuteCheckIPv4')]
	public function testExecuteCheckMatchesIPv4(string $value, string $ip, bool $expected): void {
		$check = new RequestRemoteAddress($this->getL10NMock(), $this->request);

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn($ip);

		$this->assertEquals($expected, $check->executeCheck('matchesIPv4', $value));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataExecuteCheckIPv4')]
	public function testExecuteCheckNotMatchesIPv4(string $value, string $ip, bool $expected): void {
		$check = new RequestRemoteAddress($this->getL10NMock(), $this->request);

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn($ip);

		$this->assertEquals(!$expected, $check->executeCheck('!matchesIPv4', $value));
	}

	public static function dataExecuteCheckIPv6(): array {
		return [
			['::1/128', '::1', true],
			['::2/128', '::3', false],
			['::2/127', '::3', true],
			['::1/128', '::2', false],
			['::1/127', '::2', false],
			['::1/126', '::2', true],
			['1234::1/127', '1234::', true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataExecuteCheckIPv6')]
	public function testExecuteCheckMatchesIPv6(string $value, string $ip, bool $expected): void {
		$check = new RequestRemoteAddress($this->getL10NMock(), $this->request);

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn($ip);

		$this->assertEquals($expected, $check->executeCheck('matchesIPv6', $value));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataExecuteCheckIPv6')]
	public function testExecuteCheckNotMatchesIPv6(string $value, string $ip, bool $expected): void {
		$check = new RequestRemoteAddress($this->getL10NMock(), $this->request);

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn($ip);

		$this->assertEquals(!$expected, $check->executeCheck('!matchesIPv6', $value));
	}
}
