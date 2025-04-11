<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\RequestRemoteAddress;
use OCP\IL10N;
use OCP\IRequest;

class RequestRemoteAddressTest extends \Test\TestCase {

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	protected $request;

	/**
	 * @return IL10N|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getL10NMock() {
		$l = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return sprintf($string, $args);
			});
		return $l;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->getMockBuilder(IRequest::class)
			->getMock();
	}

	public function dataExecuteCheckIPv4() {
		return [
			['127.0.0.1/32', '127.0.0.1', true],
			['127.0.0.1/32', '127.0.0.0', false],
			['127.0.0.1/31', '127.0.0.0', true],
			['127.0.0.1/32', '127.0.0.2', false],
			['127.0.0.1/31', '127.0.0.2', false],
			['127.0.0.1/30', '127.0.0.2', true],
		];
	}

	/**
	 * @dataProvider dataExecuteCheckIPv4
	 * @param string $value
	 * @param string $ip
	 * @param bool $expected
	 */
	public function testExecuteCheckMatchesIPv4($value, $ip, $expected): void {
		$check = new RequestRemoteAddress($this->getL10NMock(), $this->request);

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn($ip);

		$this->assertEquals($expected, $check->executeCheck('matchesIPv4', $value));
	}

	/**
	 * @dataProvider dataExecuteCheckIPv4
	 * @param string $value
	 * @param string $ip
	 * @param bool $expected
	 */
	public function testExecuteCheckNotMatchesIPv4($value, $ip, $expected): void {
		$check = new RequestRemoteAddress($this->getL10NMock(), $this->request);

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn($ip);

		$this->assertEquals(!$expected, $check->executeCheck('!matchesIPv4', $value));
	}

	public function dataExecuteCheckIPv6() {
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

	/**
	 * @dataProvider dataExecuteCheckIPv6
	 * @param string $value
	 * @param string $ip
	 * @param bool $expected
	 */
	public function testExecuteCheckMatchesIPv6($value, $ip, $expected): void {
		$check = new RequestRemoteAddress($this->getL10NMock(), $this->request);

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn($ip);

		$this->assertEquals($expected, $check->executeCheck('matchesIPv6', $value));
	}

	/**
	 * @dataProvider dataExecuteCheckIPv6
	 * @param string $value
	 * @param string $ip
	 * @param bool $expected
	 */
	public function testExecuteCheckNotMatchesIPv6($value, $ip, $expected): void {
		$check = new RequestRemoteAddress($this->getL10NMock(), $this->request);

		$this->request->expects($this->once())
			->method('getRemoteAddress')
			->willReturn($ip);

		$this->assertEquals(!$expected, $check->executeCheck('!matchesIPv6', $value));
	}
}
