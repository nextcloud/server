<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\AbstractStringCheck;
use OCA\WorkflowEngine\Check\RequestUserAgent;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;

class RequestUserAgentTest extends TestCase {

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	protected $request;

	/** @var RequestUserAgent */
	protected $check;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject $l */
		$l = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return sprintf($string, $args);
			});

		$this->check = new RequestUserAgent($l, $this->request);
	}

	public function dataExecuteCheck() {
		return [
			['is', 'android', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', true],
			['is', 'android', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', false],
			['is', 'android', 'Mozilla/5.0 (Linux) mirall/2.2.0', false],
			['is', 'android', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', false],
			['is', 'android', 'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v2.2.0', false],
			['!is', 'android', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', false],
			['!is', 'android', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', true],
			['!is', 'android', 'Mozilla/5.0 (Linux) mirall/2.2.0', true],
			['!is', 'android', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', true],
			['!is', 'android', 'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v2.2.0', true],

			['is', 'ios', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', false],
			['is', 'ios', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', true],
			['is', 'ios', 'Mozilla/5.0 (Linux) mirall/2.2.0', false],
			['is', 'ios', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', false],
			['is', 'ios', 'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v2.2.0', false],
			['!is', 'ios', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', true],
			['!is', 'ios', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', false],
			['!is', 'ios', 'Mozilla/5.0 (Linux) mirall/2.2.0', true],
			['!is', 'ios', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', true],
			['!is', 'ios', 'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v2.2.0', true],

			['is', 'desktop', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', false],
			['is', 'desktop', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', false],
			['is', 'desktop', 'Mozilla/5.0 (Linux) mirall/2.2.0', true],
			['is', 'desktop', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', false],
			['is', 'desktop', 'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v2.2.0', false],
			['!is', 'desktop', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', true],
			['!is', 'desktop', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', true],
			['!is', 'desktop', 'Mozilla/5.0 (Linux) mirall/2.2.0', false],
			['!is', 'desktop', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', true],
			['!is', 'desktop', 'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v2.2.0', true],

			['is', 'mail', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', false],
			['is', 'mail', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', false],
			['is', 'mail', 'Mozilla/5.0 (Linux) mirall/2.2.0', false],
			['is', 'mail', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', true],
			['is', 'mail', 'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v2.2.0', true],
			['!is', 'mail', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', true],
			['!is', 'mail', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', true],
			['!is', 'mail', 'Mozilla/5.0 (Linux) mirall/2.2.0', true],
			['!is', 'mail', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', false],
			['!is', 'mail', 'Mozilla/5.0 (Linux) Nextcloud-Thunderbird v2.2.0', false],
		];
	}

	/**
	 * @dataProvider dataExecuteCheck
	 * @param string $operation
	 * @param string $checkValue
	 * @param string $actualValue
	 * @param bool $expected
	 */
	public function testExecuteCheck($operation, $checkValue, $actualValue, $expected): void {
		$this->request->expects($this->once())
			->method('getHeader')
			->willReturn($actualValue);

		/** @var AbstractStringCheck $check */
		$this->assertEquals($expected, $this->check->executeCheck($operation, $checkValue));
	}
}
