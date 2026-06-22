<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\Federation\CalendarFederationConfig;
use OCP\GlobalScale\IConfig;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalendarFederationConfigTest extends TestCase {
	private CalendarFederationConfig $config;

	private IAppConfig&MockObject $appConfig;
	private IConfig&MockObject $gsConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->gsConfig = $this->createMock(IConfig::class);

		$this->config = new CalendarFederationConfig(
			$this->appConfig,
			$this->gsConfig,
		);
	}

	public static function provideIsFederationEnabledData(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider(methodName: 'provideIsFederationEnabledData')]
	public function testIsFederationEnabled(bool $configValue): void {
		$this->appConfig->expects(self::once())
			->method('getValueBool')
			->with('dav', 'enableCalendarFederation', true)
			->willReturn($configValue);

		$this->assertEquals($configValue, $this->config->isFederationEnabled());
	}

	public static function provideIsOutgoingServer2serverShareEnabledData(): array {
		return [
			[false, false, false],
			[false, true, true],
			[true, false, false],
			[true, false, false],
		];
	}

	#[DataProvider(methodName: 'provideIsOutgoingServer2serverShareEnabledData')]
	public function testIsOutgoingServer2serverShareEnabled(
		bool $globalScaleEnabled,
		bool $expected,
		bool $configValue,
	): void {
		$this->gsConfig->expects(self::once())
			->method('onlyInternalFederation')
			->willReturn($globalScaleEnabled);

		if (!$globalScaleEnabled) {
			$this->appConfig->expects(self::once())
				->method('getValueBool')
				->with('files_sharing', 'outgoing_server2server_share_enabled', true)
				->willReturn($configValue);
		} else {
			$this->appConfig->expects(self::never())
				->method('getValueBool');
		}

		$this->assertEquals($expected, $this->config->isOutgoingServer2serverShareEnabled());
	}

	public static function provideIsIncomingServer2serverShareEnabledData(): array {
		return [
			[false, false, false],
			[false, true, true],
			[true, false, false],
			[true, false, true],
		];
	}

	#[DataProvider(methodName: 'provideIsIncomingServer2serverShareEnabledData')]
	public function testIsIncomingServer2serverShareEnabled(
		bool $globalScaleEnabled,
		bool $expected,
		bool $configValue,
	): void {
		$this->gsConfig->expects(self::once())
			->method('onlyInternalFederation')
			->willReturn($globalScaleEnabled);

		if (!$globalScaleEnabled) {
			$this->appConfig->expects(self::once())
				->method('getValueBool')
				->with('files_sharing', 'incoming_server2server_share_enabled', true)
				->willReturn($configValue);
		} else {
			$this->appConfig->expects(self::never())
				->method('getValueBool');
		}

		$this->assertEquals($expected, $this->config->isIncomingServer2serverShareEnabled());
	}
}
