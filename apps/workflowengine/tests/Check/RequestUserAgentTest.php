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
use PHPUnit\Framework\Attributes\DataProvider;
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

	/**
	 * User agents sent by the mobile apps outside their main WebDAV client,
	 * plus regular mobile browsers that must not be treated as the apps.
	 * Each one is checked against every predefined client with "is" and "!is".
	 */
	public static function dataExecuteCheckClientVariants(): \Generator {
		$userAgents = [
			// iOS: office editor web views (NCDirectEditorAdapter)
			'Mozilla/5.0 (iPhone) Mobile Nextcloud-iOS/35.2.2' => 'ios',
			'Mozilla/5.0 (iPad) Nextcloud-iOS/35.2.2' => 'ios',
			// iOS: Text editor web view on iPhone
			'Mozilla/5.0 (iOS) Nextcloud-iOS/35.2.2 AppleWebKit/605.1.15 Mobile/15E148' => 'ios',
			// iOS: branded app
			'Mozilla/5.0 (iOS) Nextcloud-iOS/35.2.2-Brand' => 'ios',
			// Android: office editor web view (office_user_agent)
			'Mozilla/5.0 (Android 13) Mobile Nextcloud-android/35.1.0' => 'android',
			// Android: image loader without version (GlideHelper)
			'Mozilla/5.0 (Android) Nextcloud-android' => 'android',
			// Mobile browsers are not the apps
			'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1' => null,
			'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1' => null,
			'Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36' => null,
		];

		foreach ($userAgents as $userAgent => $expectedClient) {
			foreach (['android', 'ios', 'desktop', 'mail'] as $client) {
				$isClient = $client === $expectedClient;
				yield "is $client: $userAgent" => ['is', $client, $userAgent, $isClient];
				yield "!is $client: $userAgent" => ['!is', $client, $userAgent, !$isClient];
			}
		}
	}

	#[DataProvider(methodName: 'dataExecuteCheck')]
	#[DataProvider(methodName: 'dataExecuteCheckClientVariants')]
	public function testExecuteCheck(string $operation, string $checkValue, string $actualValue, bool $expected): void {
		$this->request->expects($this->once())
			->method('getHeader')
			->willReturn($actualValue);

		/** @var AbstractStringCheck $check */
		$this->assertEquals($expected, $this->check->executeCheck($operation, $checkValue));
	}
}
