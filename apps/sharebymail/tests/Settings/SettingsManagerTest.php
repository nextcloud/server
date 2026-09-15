<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareByMail\Tests\Settings;

use OCA\ShareByMail\Settings\SettingsManager;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SettingsManagerTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private SettingsManager $settingsManager;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->settingsManager = new SettingsManager($this->appConfig);
	}

	public static function settingProvider(): array {
		return [
			// setting method, config key, default
			['sendPasswordByMail', 'sendpasswordmail', 'yes'],
			['replyToInitiator', 'replyToInitiator', 'yes'],
			['ccToInitiator', 'ccToInitiator', 'no'],
		];
	}

	#[DataProvider('settingProvider')]
	public function testDefault(string $method, string $key, string $default): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('sharebymail', $key, $default)
			// no value stored, so the default is handed back
			->willReturn($default);

		$this->assertSame($default === 'yes', $this->settingsManager->$method());
	}

	#[DataProvider('settingProvider')]
	public function testEnabled(string $method, string $key, string $default): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('sharebymail', $key, $default)
			->willReturn('yes');

		$this->assertTrue($this->settingsManager->$method());
	}

	#[DataProvider('settingProvider')]
	public function testDisabled(string $method, string $key, string $default): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('sharebymail', $key, $default)
			->willReturn('no');

		$this->assertFalse($this->settingsManager->$method());
	}
}
