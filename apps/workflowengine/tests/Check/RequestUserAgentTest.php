<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\AbstractStringCheck;
use OCA\WorkflowEngine\Check\RequestUserAgent;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RequestUserAgentTest extends TestCase {
	protected IRequest&MockObject $request;
	protected RequestUserAgent $check;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		/** @var IL10N&MockObject $l */
		$l = $this->createMock(IL10N::class);
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return sprintf($string, $args);
			});

		$this->check = new RequestUserAgent($l, $this->request);
	}

	public static function dataExecuteCheck(): array {
		return [
			['is', 'android', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', true],
			['is', 'android', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', false],
			['is', 'android', 'Mozilla/5.0 (Linux) mirall/2.2.0', false],
			['is', 'android', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', false],
			['is', 'android', 'Filelink for *cloud/2.2.0', false],
			['!is', 'android', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', false],
			['!is', 'android', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', true],
			['!is', 'android', 'Mozilla/5.0 (Linux) mirall/2.2.0', true],
			['!is', 'android', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', true],
			['!is', 'android', 'Filelink for *cloud/2.2.0', true],

			['is', 'ios', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', false],
			['is', 'ios', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', true],
			['is', 'ios', 'Mozilla/5.0 (Linux) mirall/2.2.0', false],
			['is', 'ios', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', false],
			['is', 'ios', 'Filelink for *cloud/2.2.0', false],
			['!is', 'ios', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', true],
			['!is', 'ios', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', false],
			['!is', 'ios', 'Mozilla/5.0 (Linux) mirall/2.2.0', true],
			['!is', 'ios', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', true],
			['!is', 'ios', 'Filelink for *cloud/2.2.0', true],

			['is', 'desktop', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', false],
			['is', 'desktop', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', false],
			['is', 'desktop', 'Mozilla/5.0 (Linux) mirall/2.2.0', true],
			['is', 'desktop', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', false],
			['is', 'desktop', 'Filelink for *cloud/2.2.0', false],
			['!is', 'desktop', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', true],
			['!is', 'desktop', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', true],
			['!is', 'desktop', 'Mozilla/5.0 (Linux) mirall/2.2.0', false],
			['!is', 'desktop', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', true],
			['!is', 'desktop', 'Filelink for *cloud/2.2.0', true],

			['is', 'mail', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', false],
			['is', 'mail', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', false],
			['is', 'mail', 'Mozilla/5.0 (Linux) mirall/2.2.0', false],
			['is', 'mail', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', true],
			['is', 'mail', 'Filelink for *cloud/2.2.0', true],
			['!is', 'mail', 'Mozilla/5.0 (Android) Nextcloud-android/2.2.0', true],
			['!is', 'mail', 'Mozilla/5.0 (iOS) Nextcloud-iOS/2.2.0', true],
			['!is', 'mail', 'Mozilla/5.0 (Linux) mirall/2.2.0', true],
			['!is', 'mail', 'Mozilla/5.0 (Windows) Nextcloud-Outlook v2.2.0', false],
			['!is', 'mail', 'Filelink for *cloud/2.2.0', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataExecuteCheck')]
	public function testExecuteCheck(string $operation, string $checkValue, string $actualValue, bool $expected): void {
		$this->request->expects($this->once())
			->method('getHeader')
			->willReturn($actualValue);

		/** @var AbstractStringCheck $check */
		$this->assertEquals($expected, $this->check->executeCheck($operation, $checkValue));
	}
}
